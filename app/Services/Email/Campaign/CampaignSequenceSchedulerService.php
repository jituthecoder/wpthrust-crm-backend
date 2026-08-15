<?php

namespace App\Services\Email\Campaign;

use App\Models\CampaignLead;
use App\Models\CampaignLeadStep;
use App\Models\CampaignSequenceStep;
use App\Models\EmailCampaign;
use App\Services\Email\EmailSenderService;
use App\Services\Email\TemplateVariableService;
use App\Services\Email\Tracking\EmailTrackingService;
use Illuminate\Support\Facades\Log;

class CampaignSequenceSchedulerService
{
    protected EmailSenderService $senderService;
    protected TemplateVariableService $variableService;
    protected EmailTrackingService $trackingService;

    public function __construct(
        EmailSenderService $senderService,
        TemplateVariableService $variableService,
        EmailTrackingService $trackingService
    ) {
        $this->senderService = $senderService;
        $this->variableService = $variableService;
        $this->trackingService = $trackingService;
    }

    /**
     * Process all due conditional follow-up sequence steps across running campaigns.
     *
     * @return int Number of follow-up steps processed in this execution.
     */
    public function processDueSequenceSteps(): int
    {
        $processedCount = 0;

        $runningCampaigns = EmailCampaign::where('status', 'running')->with(['sequenceSteps.template', 'senders.sender'])->get();

        foreach ($runningCampaigns as $campaign) {
            $followupSteps = $campaign->sequenceSteps->where('step_number', '>', 1)->sortBy('step_number');

            if ($followupSteps->isEmpty()) {
                continue;
            }

            // Get all leads that have completed Step 1
            $leads = CampaignLead::where('email_campaign_id', $campaign->id)
                ->whereIn('status', ['sent', 'opened', 'clicked'])
                ->whereNotNull('sent_at')
                ->with('business')
                ->get();

            foreach ($leads as $lead) {
                // Check if lead is unsubscribed or business is globally unsubscribed
                if ($lead->status === 'unsubscribed' || empty($lead->business?->email)) {
                    continue;
                }

                foreach ($followupSteps as $step) {
                    // Check if lead step already recorded
                    $existingLeadStep = CampaignLeadStep::where('campaign_lead_id', $lead->id)
                        ->where('step_number', $step->step_number)
                        ->first();

                    if ($existingLeadStep) {
                        continue; // Already processed this step
                    }

                    // Calculate when this follow-up step is due
                    $baseSentAt = $lead->sent_at;
                    $dueAt = $baseSentAt->copy()->addDays($step->delay_days)->addHours($step->delay_hours);

                    if (now()->lessThan($dueAt)) {
                        break; // Not due yet, stop processing higher steps for this lead
                    }

                    // Evaluate step condition
                    $conditionMet = $this->evaluateCondition($step->condition, $lead);

                    if (!$conditionMet) {
                        // Condition not met -> mark step skipped
                        CampaignLeadStep::create([
                            'campaign_lead_id' => $lead->id,
                            'campaign_sequence_step_id' => $step->id,
                            'step_number' => $step->step_number,
                            'status' => 'skipped',
                            'sent_at' => now(),
                        ]);
                        continue;
                    }

                    // Condition met -> Dispatch follow-up step email
                    $sentSuccess = $this->dispatchFollowupEmail($campaign, $lead, $step);

                    if ($sentSuccess) {
                        CampaignLeadStep::create([
                            'campaign_lead_id' => $lead->id,
                            'campaign_sequence_step_id' => $step->id,
                            'step_number' => $step->step_number,
                            'status' => 'sent',
                            'sent_at' => now(),
                        ]);
                        $processedCount++;
                    } else {
                        CampaignLeadStep::create([
                            'campaign_lead_id' => $lead->id,
                            'campaign_sequence_step_id' => $step->id,
                            'step_number' => $step->step_number,
                            'status' => 'failed',
                            'sent_at' => now(),
                        ]);
                    }
                }
            }
        }

        return $processedCount;
    }

    /**
     * Evaluate conditional rule for a follow-up step.
     */
    protected function evaluateCondition(string $condition, CampaignLead $lead): bool
    {
        switch ($condition) {
            case 'if_opened':
                return !is_null($lead->opened_at) || $lead->status === 'opened';
            case 'if_not_opened':
                return is_null($lead->opened_at) && $lead->status !== 'opened';
            case 'if_clicked':
                return !is_null($lead->clicked_at) || $lead->status === 'clicked';
            case 'if_not_clicked':
                return is_null($lead->clicked_at) && $lead->status !== 'clicked';
            case 'always':
            default:
                return true;
        }
    }

    /**
     * Dispatch follow-up email for a specific sequence step.
     */
    protected function dispatchFollowupEmail(EmailCampaign $campaign, CampaignLead $lead, CampaignSequenceStep $step): array
    {
        try {
            $template = $step->template;
            if (!$template || !$template->currentVersion) {
                Log::warning("Sequence Step #{$step->id} has no valid published template");
                return ['success' => false, 'subject' => null, 'html' => null];
            }

            $version = $template->currentVersion;
            $senderPivot = $campaign->senders->first();
            $sender = $senderPivot?->sender;

            if (!$sender) {
                Log::warning("Campaign #{$campaign->id} has no active sender for sequence step");
                return ['success' => false, 'subject' => null, 'html' => null];
            }

            // Render subject and body with lead variable replacement
            $subject = $this->variableService->replaceVariables($version->subject ?? 'Follow up', $lead->business, $lead);
            $rawHtml = $this->variableService->replaceVariables($version->body_html ?? '', $lead->business, $lead);
            $trackedHtml = $this->trackingService->prepareTrackedHtml($rawHtml, $lead);

            $sendResult = $this->senderService->sendHtmlEmail(
                $sender,
                $lead->business->email,
                $subject,
                $trackedHtml
            );

            return [
                'success' => $sendResult['success'] ?? false,
                'subject' => $subject,
                'html' => $rawHtml,
            ];
        } catch (\Throwable $e) {
            Log::error("Failed to dispatch sequence step #{$step->id} for Lead #{$lead->id}: " . $e->getMessage());
            return ['success' => false, 'subject' => null, 'html' => null];
        }
    }
}
