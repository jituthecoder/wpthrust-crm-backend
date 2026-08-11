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
        | Filter Senders By Capacity Engine
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
        | Determine Lead Position
        |--------------------------------------------------------------------------
        |
        | Find how many campaign leads exist before this lead.
        |
        */

        $leadPosition = CampaignLead::where(
            'email_campaign_id',
            $lead->email_campaign_id
        )
        ->where('id', '<=', $lead->id)
        ->count() - 1;

        /*
        |--------------------------------------------------------------------------
        | Round Robin
        |--------------------------------------------------------------------------
        */

        $senderIndex = $leadPosition % $availableSenders->count();

        return $availableSenders->get($senderIndex);
    }
}