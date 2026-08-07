<?php

namespace App\Services\Email\Campaign;

use App\Mail\CampaignEmail;
use App\Models\CampaignLead;
use App\Models\EmailSender;
use App\Services\Email\ProviderFactory;

class CampaignMailerService
{
    /**
     * Send Campaign Email
     */
    public function send(
        CampaignLead $lead,
        EmailSender $sender,
        string $subject,
        string $html,
        ?string $plainText = null
    ): bool {

        /*
        |--------------------------------------------------------------------------
        | Validate Recipient
        |--------------------------------------------------------------------------
        */

        if (empty($lead->business->email)) {

            throw new \Exception(
                'Business email not found.'
            );

        }

        /*
        |--------------------------------------------------------------------------
        | Create Provider
        |--------------------------------------------------------------------------
        */

        $provider = ProviderFactory::make($sender);

        /*
        |--------------------------------------------------------------------------
        | Build Email
        |--------------------------------------------------------------------------
        */

        $mailable = (new CampaignEmail(
            $subject,
            $html,
            $plainText
        ))
            ->to(
                $lead->business->email,
                $lead->business->business_name
            )
            ->from(
                $sender->email,
                $sender->display_name
            );

        /*
        |--------------------------------------------------------------------------
        | Send Email
        |--------------------------------------------------------------------------
        */

        $provider->send(
            $sender->senderAccount->settings,
            $mailable
        );

        return true;
    }
}