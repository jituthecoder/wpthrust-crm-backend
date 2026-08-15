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
        if (empty($business->email)) {
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

                // Attach business as a pending lead scheduled for immediate dispatch
                $lead = CampaignLead::create([
                    'email_campaign_id' => $campaign->id,
                    'business_id' => $business->id,
                    'status' => 'pending',
                    'scheduled_at' => now(),
                ]);

                $campaign->increment('total_leads');
                $attachedCount++;

                // Dispatch email job immediately if campaign is running
                if ($campaign->status === 'running') {
                    try {
                        \App\Jobs\SendCampaignLeadJob::dispatch($lead);
                    } catch (\Throwable $e) {
                        Log::warning("Failed to dispatch auto-synced lead #{$lead->id}: " . $e->getMessage());
                    }
                }

                Log::info("Auto-synced Lead #{$business->id} ({$business->email}) to Campaign #{$campaign->id} ({$campaign->name})");
            }
        }

        return $attachedCount;
    }

    /**
     * Check if a business lead satisfies the campaign's auto-sync filter criteria.
     */
    public function matchesCriteria(Business $business, array $criteria): bool
    {
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

        // 4. Country Substring Check
        if (!empty($criteria['country'])) {
            $country = strtolower(trim($business->country ?? ''));
            $targetCountry = strtolower(trim($criteria['country']));
            if (!str_contains($country, $targetCountry)) {
                return false;
            }
        }

        // 5. PSI Score Range Check
        if (!empty($criteria['psi_filter'])) {
            $score = (int) ($business->audit?->mobile_pagespeed ?? 0);
            $hasAudit = !empty($business->audit);

            switch ($criteria['psi_filter']) {
                case 'less_50':
                    if (!$hasAudit || $score <= 0 || $score >= 50) return false;
                    break;
                case 'less_90':
                    if (!$hasAudit || $score <= 0 || $score >= 90) return false;
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
}
