<?php

namespace App\Services\Email\Campaign;

use App\Models\Business;
use App\Models\CampaignLead;
use App\Models\EmailCampaign;
use Illuminate\Support\Facades\Log;

class CampaignAutoSyncService
{
    /**
     * Evaluate a newly imported or updated Business lead against all active auto-sync campaigns.
     *
     * @param Business $business
     * @return int Count of campaigns the business was attached to.
     */
    public function syncMatchingLeads(Business $business): int
    {
        if (empty($business->email) || $business->is_bounced || !empty($business->bounced_at) || $business->lead_status === 'bounced') {
            return 0;
        }

        $attachedCount = 0;

        // Support running, completed, and scheduled campaigns with auto-sync enabled
        $autoSyncCampaigns = EmailCampaign::where('auto_sync_enabled', true)
            ->whereIn('status', ['running', 'completed', 'scheduled'])
            ->get();

        foreach ($autoSyncCampaigns as $campaign) {
            // Check if already in campaign
            $alreadyExists = CampaignLead::where('email_campaign_id', $campaign->id)
                ->where('business_id', $business->id)
                ->exists();

            if ($alreadyExists) {
                continue;
            }

            $criteria = $campaign->auto_sync_criteria ?? [];

            if ($this->matchesCriteria($business, $criteria)) {
                // Reactivate completed campaign to running if new leads arrive
                if ($campaign->status === 'completed') {
                    $campaign->update(['status' => 'running']);
                }

                // Attach business as a pending lead scheduled for delivery via CampaignDeliverySchedulerService
                $lead = CampaignLead::create([
                    'email_campaign_id' => $campaign->id,
                    'business_id' => $business->id,
                    'status' => 'pending',
                    'scheduled_at' => now(),
                ]);

                $campaign->increment('total_leads');
                $attachedCount++;

                Log::info("Auto-synced Lead #{$business->id} ({$business->email}) to Campaign #{$campaign->id} ({$campaign->name})");
            }
        }

        return $attachedCount;
    }

    /**
     * Scan database and auto-attach ALL existing matching leads to a specific campaign.
     */
    public function syncAllMatchingLeads(EmailCampaign $campaign): int
    {
        if (!$campaign->auto_sync_enabled) {
            return 0;
        }

        $criteria = $campaign->auto_sync_criteria ?? [];
        $attachedCount = 0;

        // Fetch candidate businesses with emails that are not already attached
        $existingBusinessIds = CampaignLead::where('email_campaign_id', $campaign->id)
            ->pluck('business_id')
            ->toArray();

        $query = Business::whereNotNull('email')
            ->where('email', '!=', '')
            ->neverBounced()
            ->whereNotIn('id', $existingBusinessIds)
            ->with('audit');

        $candidateLeads = $query->get();

        foreach ($candidateLeads as $business) {
            if ($this->matchesCriteria($business, $criteria)) {

                // Attach business as pending lead scheduled for delivery via CampaignDeliverySchedulerService
                $lead = CampaignLead::create([
                    'email_campaign_id' => $campaign->id,
                    'business_id' => $business->id,
                    'status' => 'pending',
                    'scheduled_at' => now(),
                ]);

                $campaign->increment('total_leads');
                $attachedCount++;

                Log::info("Manual/Auto-synced Lead #{$business->id} ({$business->email}) to Campaign #{$campaign->id} ({$campaign->name})");
            }
        }

        if ($attachedCount > 0 && $campaign->status === 'completed') {
            $campaign->update(['status' => 'running']);
        }

        return $attachedCount;
    }

    /**
     * Check if a business lead satisfies the campaign's auto-sync filter criteria.
     */
    public function matchesCriteria(Business $business, array $criteria): bool
    {
        // Always exclude bounced leads unless explicitly allowed
        if (($criteria['exclude_bounced'] ?? true) && ($business->is_bounced || !empty($business->bounced_at) || $business->lead_status === 'bounced')) {
            return false;
        }

        if (empty($criteria)) {
            return true; // No strict criteria -> match all leads with email
        }

        // 1. Has Website Check
        if (!empty($criteria['has_website'])) {
            $hasSite = !empty($business->website) && $business->website !== '-';
            if ($criteria['has_website'] === 'yes' && !$hasSite) return false;
            if ($criteria['has_website'] === 'no' && $hasSite) return false;
        }

        // 2. Has Screenshot Check
        if (!empty($criteria['has_screenshot'])) {
            $hasScreenshot = !empty($business->audit?->mobile_screenshot_path);
            if ($criteria['has_screenshot'] === 'yes' && !$hasScreenshot) return false;
            if ($criteria['has_screenshot'] === 'no' && $hasScreenshot) return false;
        }

        // 3. Category Substring Check
        if (!empty($criteria['category'])) {
            $cat = strtolower(trim($business->category ?? ''));
            $targetCat = strtolower(trim($criteria['category']));
            if (!str_contains($cat, $targetCat)) {
                return false;
            }
        }

        // 4. Country Substring & Alias Check
        if (!empty($criteria['country'])) {
            $country = strtolower(trim($business->country ?? ''));
            $targetCountry = strtolower(trim($criteria['country']));
            if (!$this->matchCountryAlias($country, $targetCountry)) {
                return false;
            }
        }

        // 5. PSI Score Range Check
        if (!empty($criteria['psi_filter'])) {
            $score = (int) ($business->audit?->mobile_pagespeed ?? 0);
            $hasAudit = !empty($business->audit);

            switch ($criteria['psi_filter']) {
                case 'less_30':
                    if (!$hasAudit || $score <= 0 || $score >= 30) return false;
                    break;
                case 'less_50':
                    if (!$hasAudit || $score <= 0 || $score >= 50) return false;
                    break;
                case 'less_70':
                    if (!$hasAudit || $score <= 0 || $score >= 70) return false;
                    break;
                case 'less_90':
                    if (!$hasAudit || $score <= 0 || $score >= 90) return false;
                    break;
                case 'between_50_89':
                    if (!$hasAudit || $score < 50 || $score >= 90) return false;
                    break;
                case 'good_90':
                    if (!$hasAudit || $score < 90) return false;
                    break;
                case 'not_audited':
                    if ($hasAudit && $score > 0) return false;
                    break;
            }
        }

        return true;
    }

    /**
     * Smart Country Alias Matching (supports USA, US, United States, United State, etc.)
     */
    protected function matchCountryAlias(string $leadCountry, string $targetCountry): bool
    {
        if (empty($targetCountry)) return true;
        if (empty($leadCountry)) return false;

        if (str_contains($leadCountry, $targetCountry) || str_contains($targetCountry, $leadCountry)) {
            return true;
        }

        $usAliases = ['us', 'usa', 'united states', 'united state', 'united states of america', 'u.s.', 'u.s.a.'];
        if (in_array($targetCountry, $usAliases) && in_array($leadCountry, $usAliases)) {
            return true;
        }

        $ukAliases = ['uk', 'united kingdom', 'great britain', 'england', 'u.k.'];
        if (in_array($targetCountry, $ukAliases) && in_array($leadCountry, $ukAliases)) {
            return true;
        }

        return false;
    }
}
