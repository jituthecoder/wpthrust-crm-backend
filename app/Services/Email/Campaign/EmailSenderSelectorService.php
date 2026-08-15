<?php

namespace App\Services\Email\Campaign;

use App\Models\CampaignLead;
use App\Models\CampaignSender;
use App\Services\Email\Campaign\SenderCapacityService;

class EmailSenderSelectorService
{
    /**
     * Select the best sender for a lead.
     */
    public function select(CampaignLead $lead): ?CampaignSender
    {
        /*
        |--------------------------------------------------------------------------
        | Active Senders
        |--------------------------------------------------------------------------
        */

        $senders = CampaignSender::with('sender')
            ->where(
                'email_campaign_id',
                $lead->email_campaign_id
            )
            ->where('is_active', true)
            ->orderBy('priority')
            ->orderBy('id')
            ->get();

        if ($senders->isEmpty()) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Filter & Prioritize Senders By Hourly Capacity Ratio
        |--------------------------------------------------------------------------
        */

        $capacityService = app(SenderCapacityService::class);

        $availableSenders = $senders->filter(function ($campaignSender) use ($capacityService) {
            $sender = $campaignSender->sender;
            if (!$sender) {
                return false;
            }
            return $capacityService->canReserve($sender);
        })->values();

        if ($availableSenders->isEmpty()) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Capacity-Weighted Round Robin
        |--------------------------------------------------------------------------
        | Select sender with lowest current hourly utilization ratio (reserved / limit)
        | so senders with larger limits (e.g. 60/hr) receive proportionally more leads
        | than senders with smaller limits (e.g. 30/hr).
        |
        */

        return $availableSenders->sortBy(function ($campaignSender) use ($capacityService) {
            $sender = $campaignSender->sender;
            $capacityService->checkAndApplyWindowResets($sender);

            $hourlyLimit = max(1, (int) ($sender->hourly_limit ?? 20));
            $reservedThisHour = (int) ($sender->reserved_this_hour ?? 0);

            return $reservedThisHour / (float) $hourlyLimit;
        })->first();
    }
}