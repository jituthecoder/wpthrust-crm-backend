<?php

namespace App\Services\Email\Campaign;

use App\Models\CampaignLead;
use App\Models\CampaignSender;

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
        | Filter Senders By Limits
        |--------------------------------------------------------------------------
        */

        $availableSenders = $senders->filter(function ($campaignSender) {

            $sender = $campaignSender->sender;

            if (!$sender) {
                return false;
            }

            /*
            |--------------------------------------------------------------------------
            | Daily Limit
            |--------------------------------------------------------------------------
            */

            if (
                $sender->daily_limit &&
                $sender->sent_today >= $sender->daily_limit
            ) {
                return false;
            }

            /*
            |--------------------------------------------------------------------------
            | Hourly Limit
            |--------------------------------------------------------------------------
            */

            if (
                $sender->hourly_limit &&
                $sender->sent_this_hour >= $sender->hourly_limit
            ) {
                return false;
            }

            return true;

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