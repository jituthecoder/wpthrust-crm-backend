<?php

namespace App\Jobs;

use App\Models\CampaignLead;
use App\Services\Email\Campaign\CampaignMailerService;
use App\Services\Email\Campaign\EmailSenderSelectorService;
use App\Services\Email\Campaign\TemplateRendererService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendCampaignLeadJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /**
     * Campaign Lead ID
     */
    protected int $campaignLeadId;

    /**
     * Retry Attempts
     */
    public int $tries = 3;

    /**
     * Timeout
     */
    public int $timeout = 120;

    /**
     * Create Job
     */
    public function __construct(int $campaignLeadId)
    {
        $this->campaignLeadId = $campaignLeadId;
    }

    /**
     * Execute Job
     */
    public function handle(
        EmailSenderSelectorService $senderSelector,
        TemplateRendererService $renderer,
        CampaignMailerService $mailer
    ): void {

        /*
        |--------------------------------------------------------------------------
        | Load Lead
        |--------------------------------------------------------------------------
        */

        $lead = CampaignLead::with([

            'campaign',

            'business',

            'campaign.template.currentVersion',

        ])->find($this->campaignLeadId);

        if (!$lead) {
            return;
        }

        if ($lead->status !== 'pending') {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Mark Processing
        |--------------------------------------------------------------------------
        */

        $lead->update([

            'status' => 'processing',

        ]);

        try {

            /*
            |--------------------------------------------------------------------------
            | Select Sender
            |--------------------------------------------------------------------------
            */

            $campaignSender = $senderSelector->select($lead);

            if (!$campaignSender) {

                throw new \Exception(
                    'No sender available.'
                );

            }

            $sender = $campaignSender->sender;

            /*
            |--------------------------------------------------------------------------
            | Template
            |--------------------------------------------------------------------------
            */

            $version = $lead
                ->campaign
                ->template
                ->currentVersion;

            if (!$version) {

                throw new \Exception(
                    'Template version not found.'
                );

            }

            /*
            |--------------------------------------------------------------------------
            | Render Template
            |--------------------------------------------------------------------------
            */

            $subject = $renderer->renderSubject(
                $version->subject,
                $lead->business
            );

            $html = $renderer->renderHtml(
                $version->html,
                $lead->business
            );

            $plain = $renderer->renderPlainText(
                $version->plain_text,
                $lead->business
            );

            /*
            |--------------------------------------------------------------------------
            | Send Email
            |--------------------------------------------------------------------------
            */

            $mailer->send(
                $lead,
                $sender,
                $subject,
                $html,
                $plain
            );

            /*
            |--------------------------------------------------------------------------
            | Update Lead
            |--------------------------------------------------------------------------
            */

            $lead->update([

                'status' => 'sent',

                'email_sender_id' => $sender->id,

                'email_template_version_id' => $version->id,

                'sent_at' => now(),

            ]);

            /*
            |--------------------------------------------------------------------------
            | Update Sender Statistics
            |--------------------------------------------------------------------------
            */

            $sender->increment('sent_today');

            $sender->increment('sent_this_hour');

            $sender->update([

                'last_sent_at' => now(),

            ]);

        } catch (\Throwable $e) {

            $lead->update([

                'status' => 'failed',

                'failure_reason' => $e->getMessage(),

                'retry_count' => $lead->retry_count + 1,

            ]);

            throw $e;

        }
    }
}