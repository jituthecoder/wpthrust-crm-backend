<?php

namespace App\Services\Email\Campaign;

use App\Mail\CampaignEmail;
use App\Models\CampaignLead;
use App\Models\EmailSender;
use App\Services\Email\ProviderFactory;
use App\Services\Email\Providers\ProviderDeliveryResult;
use App\Services\Email\Tracking\EmailTrackingService;

class CampaignMailerService
{
    public function __construct(
        protected EmailTrackingService $trackingService = new EmailTrackingService()
    ) {}

    /**
     * Send Campaign Email
     */
    public function send(
        CampaignLead $lead,
        EmailSender $sender,
        string $subject,
        string $html,
        ?string $plainText = null
    ): ProviderDeliveryResult {

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
        | Inject Open Pixel & Click Tracking Links
        |--------------------------------------------------------------------------
        */

        $trackedHtml = $this->trackingService->prepareTrackedHtml($html, $lead);

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
            $trackedHtml,
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

        $settings = $sender->senderAccount?->settings ?? [];

        return $provider->send(
            $settings,
            $mailable
        );
    }
}