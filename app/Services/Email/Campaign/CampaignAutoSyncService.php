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

        $autoSyncCampaigns = EmailCampaign::where('status', 'running')
            ->where('auto_sync_enabled', true)
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
                // Attach business as a pending lead scheduled for immediate dispatch
                CampaignLead::create([
                    'email_campaign_id' => $campaign->id,
                    'business_id' => $business->id,
                    'status' => 'pending',
                    'scheduled_at' => now()->subMinutes(5),
                ]);

                $campaign->increment('total_leads');
                $attachedCount++;

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

        // 3. Category Check
        if (!empty($criteria['category'])) {
            if (strtolower(trim($business->category ?? '')) !== strtolower(trim($criteria['category']))) {
                return false;
            }
        }

        // 4. PSI Score Range Check
        if (!empty($criteria['psi_filter'])) {
            $score = (int) ($business->audit?->mobile_pagespeed ?? 0);
            $hasAudit = !empty($business->audit);

            switch ($criteria['psi_filter']) {
                case 'less_50':
                    if (!$hasAudit || $score >= 50) return false;
                    break;
                case 'less_90':
                    if (!$hasAudit || $score >= 90) return false;
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
