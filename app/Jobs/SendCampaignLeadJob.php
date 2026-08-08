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
use App\Services\Email\EmailCampaignService;

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
        CampaignMailerService $mailer,
        EmailCampaignService $campaignService
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

        /*
        |--------------------------------------------------------------------------
        | Check Lead Status
        |--------------------------------------------------------------------------
        */

        if ($lead->status !== 'pending') {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Check Campaign Status
        |--------------------------------------------------------------------------
        |
        | Only send emails when the campaign is running.
        |
        */

        if (!$lead->campaign) {
            return;
        }

        if ($lead->campaign->status !== 'running') {
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

            if (!$sender) {

                throw new \Exception(
                    'Email sender not found.'
                );

            }

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
            | Check Campaign Status Again
            |--------------------------------------------------------------------------
            |
            | The campaign could have been paused while this job
            | was processing.
            |
            */

            $lead->campaign->refresh();

            if ($lead->campaign->status !== 'running') {

                $lead->update([

                    'status' => 'pending',

                ]);

                return;
            }

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

                'status' =>
                    'sent',

                'email_sender_id' =>
                    $sender->id,

                'email_template_version_id' =>
                    $version->id,

                'sent_at' =>
                    now(),

            ]);

            /*
            |--------------------------------------------------------------------------
            | Check Campaign Completion
            |--------------------------------------------------------------------------
            */

            $campaignService->completeIfFinished(
                $lead->campaign
            );

            /*
            |--------------------------------------------------------------------------
            | Update Global Sender Statistics
            |--------------------------------------------------------------------------
            */

            $sender->increment(
                'sent_today'
            );

            $sender->increment(
                'sent_this_hour'
            );

            $sender->update([

                'last_sent_at' => now(),

            ]);

            /*
            |--------------------------------------------------------------------------
            | Update Campaign Sender Statistics
            |--------------------------------------------------------------------------
            */

            $campaignSender->increment(
                'sent_count'
            );

            $campaignSender->update([

                'last_sent_at' => now(),

            ]);

        } catch (\Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Update Lead Failure
            |--------------------------------------------------------------------------
            */

            $lead->update([

                'status' =>
                    'failed',

                'failure_reason' =>
                    $e->getMessage(),

                'retry_count' =>
                    $lead->retry_count + 1,

            ]);

            /*
            |--------------------------------------------------------------------------
            | Update Campaign Sender Failure Statistics
            |--------------------------------------------------------------------------
            */

            if (isset($campaignSender)) {

                $campaignSender->increment(
                    'failed_count'
                );

            }

            throw $e;
        }
    }
}