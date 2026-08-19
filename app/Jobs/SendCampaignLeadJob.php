<?php

namespace App\Jobs;

use App\Models\CampaignLead;
use App\Services\Email\Campaign\CampaignMailerService;
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
        $this->onQueue('emails');
    }

    /**
     * Execute Job
     */
    public function handle(
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
            'sender',
        ])->find($this->campaignLeadId);

        if (!$lead) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Check Lead Status
        |--------------------------------------------------------------------------
        */

        if ($lead->status !== 'pending' && $lead->status !== 'processing') {
            return;
        }

        $email = $lead->business?->email;
        $orgId = $lead->campaign?->organization_id;
        $campaignId = $lead->email_campaign_id;

        $isUnsubscribed = $lead->status === 'unsubscribed' || ($email && \App\Models\UnsubscribedEmail::where('email', $email)
            ->where(function ($query) use ($orgId, $campaignId) {
                $query->where('campaign_id', $campaignId);
                if ($orgId) {
                    $query->orWhere('organization_id', $orgId);
                }
            })->exists());

        if ($isUnsubscribed) {
            $lead->update(['status' => 'unsubscribed', 'unsubscribed_at' => $lead->unsubscribed_at ?? now()]);
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Check Campaign
        |--------------------------------------------------------------------------
        */

        if (!$lead->campaign) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Check Campaign Status
        |--------------------------------------------------------------------------
        */

        if ($lead->campaign->status !== 'running') {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Get Email Sender
        |--------------------------------------------------------------------------
        */

        $sender = $lead->sender;

        // If pre-assigned sender is inactive or no longer attached to campaign, reset
        if ($sender && (!$sender->is_active || !\App\Models\CampaignSender::where('email_campaign_id', $lead->email_campaign_id)->where('email_sender_id', $sender->id)->where('is_active', true)->exists())) {
            $sender = null;
        }

        if (!$sender && $lead->campaign) {
            $senderSelector = app(\App\Services\Email\Campaign\EmailSenderSelectorService::class);
            $selectedCampaignSender = $senderSelector->select($lead);
            if ($selectedCampaignSender) {
                $sender = $selectedCampaignSender->sender;
            }
        }

        if (!$sender) {
            $hasActiveCampaignSenders = \App\Models\CampaignSender::where('email_campaign_id', $lead->email_campaign_id)
                ->where('is_active', true)
                ->whereHas('sender', function ($q) {
                    $q->where('is_active', true);
                })
                ->exists();

            if ($hasActiveCampaignSenders) {
                // Active senders are configured but currently at capacity/limits.
                // Release back to queue to retry in 60s without marking lead as failed.
                $this->release(60);
                return;
            }

            $lead->update([
                'status' => 'failed',
                'failure_reason' => 'No active email sender configured for this campaign.',
                'retry_count' => $lead->retry_count + 1,
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Idempotency Claim & Attempt Tracking
        |--------------------------------------------------------------------------
        */

        $attemptNumber = $lead->retry_count + 1;
        $orgId = $lead->campaign->organization_id;
        $idempotencyKey = \App\Models\CampaignDeliveryAttempt::generateIdempotencyKey(
            $orgId,
            $lead->email_campaign_id,
            $lead->id,
            $attemptNumber
        );

        $attempt = \Illuminate\Support\Facades\DB::transaction(function () use (
            $orgId,
            $lead,
            $sender,
            $attemptNumber,
            $idempotencyKey
        ) {
            $existingAttempt = \App\Models\CampaignDeliveryAttempt::where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($existingAttempt) {
                return $existingAttempt;
            }

            return \App\Models\CampaignDeliveryAttempt::create([
                'organization_id' => $orgId,
                'email_campaign_id' => $lead->email_campaign_id,
                'campaign_lead_id' => $lead->id,
                'email_sender_id' => $sender->id,
                'attempt_number' => $attemptNumber,
                'idempotency_key' => $idempotencyKey,
                'status' => 'sending',
                'started_at' => now(),
            ]);
        });

        // If attempt was already completed as sent, ensure lead reflects sent status and return
        if ($attempt->status === 'sent') {
            $lead->update([
                'status' => 'sent',
                'sent_at' => $attempt->completed_at ?? now(),
                'provider_message_id' => $attempt->provider_message_id,
                'provider_thread_id' => $attempt->provider_thread_id,
                'processing_started_at' => null,
            ]);
            return;
        }

        // If attempt has unknown status (post-crash recovery pending reconciliation), do not resend blindly
        if ($attempt->status === 'unknown') {
            \Illuminate\Support\Facades\Log::warning('SendCampaignLeadJob skipped attempt with unknown outcome', [
                'campaign_lead_id' => $lead->id,
                'attempt_id' => $attempt->id,
                'idempotency_key' => $idempotencyKey,
            ]);
            return;
        }

        if ($attempt->status !== 'sending') {
            $attempt->update([
                'status' => 'sending',
                'started_at' => now(),
                'email_sender_id' => $sender->id,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Reserve Sender Capacity
        |--------------------------------------------------------------------------
        */

        $capacityService = app(\App\Services\Email\Campaign\SenderCapacityService::class);

        $capacityReserved = $capacityService->reserveCapacity($sender);

        if (!$capacityReserved) {
            $attempt->update(['status' => 'pending']);
            $lead->update([
                'status' => 'pending',
                'processing_started_at' => null,
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Mark Processing
        |--------------------------------------------------------------------------
        */

        $lead->update([
            'status' => 'processing',
            'processing_started_at' => now(),
            'last_attempt_at' => now(),
        ]);

        try {

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

            $targetEntity = $lead->contactListLead ?? $lead->business;

            /*
            |--------------------------------------------------------------------------
            | Render Subject
            |--------------------------------------------------------------------------
            */

            $subject = $renderer->renderSubject(
                $version->subject,
                $targetEntity
            );

            /*
            |--------------------------------------------------------------------------
            | Render HTML
            |--------------------------------------------------------------------------
            */

            $html = $renderer->renderHtml(
                $version->html,
                $targetEntity
            );

            /*
            |--------------------------------------------------------------------------
            | Render Plain Text
            |--------------------------------------------------------------------------
            */

            $plain = $renderer->renderPlainText(
                $version->plain_text,
                $targetEntity
            );

            /*
            |--------------------------------------------------------------------------
            | Check Campaign Status Again
            |--------------------------------------------------------------------------
            */

            $lead->campaign->refresh();

            if ($lead->campaign->status !== 'running') {

                $capacityService->releaseCapacity($sender);

                $attempt->update(['status' => 'pending']);
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

            $result = $mailer->send(
                $lead,
                $sender,
                $subject,
                $html,
                $plain
            );

            if ($result->isFailure()) {
                $err = $result->errorMessage ?? 'Email delivery failed via provider.';
                $lowerErr = strtolower($err);

                // Detect OAuth / Authentication failures (JWT, token expired, invalid_grant, authentication failed)
                $isAuthFailure = str_contains($lowerErr, 'token') ||
                                 str_contains($lowerErr, 'jwt') ||
                                 str_contains($lowerErr, 'authentication') ||
                                 str_contains($lowerErr, 'reconnect') ||
                                 str_contains($lowerErr, 'unauthorized') ||
                                 str_contains($lowerErr, 'invalid_grant');

                if ($isAuthFailure) {
                    $sender->update([
                        'is_active' => false,
                        'requires_reauth' => true,
                        'error_message' => $err,
                    ]);
                    \Illuminate\Support\Facades\Log::warning("Sender ID {$sender->id} ({$sender->email}) disabled due to auth failure: {$err}");
                }

                throw new \Exception($err);
            }

            /*
            |--------------------------------------------------------------------------
            | Mark Sent
            |--------------------------------------------------------------------------
            */

            $attempt->update([
                'status' => 'sent',
                'completed_at' => now(),
                'sent_subject' => $subject,
                'sent_body_html' => $html,
                'provider_message_id' => $result->providerMessageId,
                'provider_thread_id' => $result->providerThreadId,
            ]);

            $lead->update([
                'status' => 'sent',
                'email_sender_id' => $sender->id,
                'email_template_version_id' => $version->id,
                'sent_subject' => $subject,
                'sent_body_html' => $html,
                'provider_message_id' => $result->providerMessageId,
                'provider_thread_id' => $result->providerThreadId,
                'sent_at' => now(),
                'processing_started_at' => null,
            ]);

            if ($lead->campaign) {
                $lead->campaign->increment('sent_count');
            }

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

            $capacityService->recordSendSuccess(
                $sender
            );

            /*
            |--------------------------------------------------------------------------
            | Update Campaign Sender Statistics
            |--------------------------------------------------------------------------
            */

            $campaignSenderPivot = \App\Models\CampaignSender::where('email_campaign_id', $lead->email_campaign_id)
                ->where('email_sender_id', $sender->id)
                ->first();

            if ($campaignSenderPivot) {
                $campaignSenderPivot->increment('sent_count');
                $campaignSenderPivot->update(['last_sent_at' => now()]);
            }

        } catch (\Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Release Capacity Reservation On Failure
            |--------------------------------------------------------------------------
            */

            $capacityService->releaseCapacity($sender);

            /*
            |--------------------------------------------------------------------------
            | Update Lead Failure
            |--------------------------------------------------------------------------
            */

            $sanitizedReason = \App\Services\Email\Providers\ProviderSanitizer::sanitizeMessage($e->getMessage());

            $attempt->update([
                'status' => 'failed',
                'completed_at' => now(),
                'failure_reason' => $sanitizedReason,
            ]);

            $lead->update([
                'status' => 'failed',
                'failure_reason' => $sanitizedReason,
                'retry_count' => $lead->retry_count + 1,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Update Campaign Sender Failure Statistics
            |--------------------------------------------------------------------------
            */

            $campaignSenderPivot = \App\Models\CampaignSender::where('email_campaign_id', $lead->email_campaign_id)
                ->where('email_sender_id', $sender->id)
                ->first();

            if ($campaignSenderPivot) {
                $campaignSenderPivot->increment('failed_count');
            }

            throw $e;
        }
    }
}