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
            ->where('email_campaign_id', $lead->email_campaign_id)
            ->where('is_active', true)
            ->orderBy('priority')
            ->orderBy('id')
            ->get();

        if ($senders->isEmpty()) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Daily / Hourly Limit
        |--------------------------------------------------------------------------
        */

        foreach ($senders as $campaignSender) {

            $sender = $campaignSender->sender;

            if (!$sender) {
                continue;
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
                continue;
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
                continue;
            }

            return $campaignSender;
        }

        return null;
    }
}