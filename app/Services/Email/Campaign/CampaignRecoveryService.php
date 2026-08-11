<?php

namespace App\Services\Email\Campaign;

use App\Models\CampaignLead;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CampaignRecoveryService
{
    /**
     * Recover stale processing campaign leads safely across all campaigns/tenants.
     *
     * @param int|null $timeoutMinutes
     * @param int $chunkSize
     * @return int Total count of recovered leads
     */
    public function recoverStaleLeads(?int $timeoutMinutes = null, int $chunkSize = 100): int
    {
        $timeout = $timeoutMinutes ?? (int) config('campaign.processing_timeout_minutes', 10);
        $recoveredCount = 0;

        CampaignLead::staleProcessing($timeout)
            ->orderBy('id')
            ->chunkById($chunkSize, function ($leads) use ($timeout, &$recoveredCount) {
                foreach ($leads as $lead) {
                    if ($this->recoverLead($lead->id, $timeout)) {
                        $recoveredCount++;
                    }
                }
            });

        return $recoveredCount;
    }

    /**
     * Atomically recover a single stale processing lead.
     */
    public function recoverLead(int $leadId, int $timeoutMinutes): bool
    {
        return DB::transaction(function () use ($leadId, $timeoutMinutes) {
            $lead = CampaignLead::where('id', $leadId)
                ->staleProcessing($timeoutMinutes)
                ->lockForUpdate()
                ->first();

            if (!$lead) {
                return false;
            }

            $previousStatus = $lead->status;
            $sender = $lead->sender;

            // Update latest attempt status if it was left in 'sending' state
            $latestAttempt = $lead->latestAttempt;
            if ($latestAttempt && $latestAttempt->status === 'sending') {
                $latestAttempt->update([
                    'status' => 'unknown',
                    'completed_at' => now(),
                    'failure_reason' => 'Interrupted by application crash during provider delivery call.',
                ]);
            }

            // Reconcile sender capacity reservation if sender was assigned
            if ($sender) {
                app(SenderCapacityService::class)->releaseCapacity($sender);
            }

            $newRetryCount = $lead->retry_count + 1;

            if ($newRetryCount >= $lead->max_retry) {
                // Exhausted retries -> mark failed
                $lead->update([
                    'status' => 'failed',
                    'retry_count' => $newRetryCount,
                    'processing_started_at' => null,
                    'failure_reason' => 'Delivery abandoned after exceeding processing recovery attempts.',
                ]);

                Log::info('Stale campaign lead delivery abandoned (max retries reached)', [
                    'email_campaign_id' => $lead->email_campaign_id,
                    'campaign_lead_id' => $lead->id,
                    'email_sender_id' => $lead->email_sender_id,
                    'previous_status' => $previousStatus,
                    'new_status' => 'failed',
                    'retry_count' => $newRetryCount,
                    'recovered_at' => now()->toIso8601String(),
                ]);
            } else {
                // Restore to pending with future scheduled_at pacing
                $nextScheduledAt = now()->addSeconds(60);

                $lead->update([
                    'status' => 'pending',
                    'retry_count' => $newRetryCount,
                    'processing_started_at' => null,
                    'failure_reason' => null,
                    'scheduled_at' => $nextScheduledAt,
                ]);

                Log::info('Stale campaign lead recovered to pending', [
                    'email_campaign_id' => $lead->email_campaign_id,
                    'campaign_lead_id' => $lead->id,
                    'email_sender_id' => $lead->email_sender_id,
                    'previous_status' => $previousStatus,
                    'new_status' => 'pending',
                    'retry_count' => $newRetryCount,
                    'next_scheduled_at' => $nextScheduledAt->toIso8601String(),
                    'recovered_at' => now()->toIso8601String(),
                ]);
            }

            return true;
        });
    }
}
