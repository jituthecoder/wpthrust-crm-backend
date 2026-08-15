<?php

namespace App\Services\Email\Campaign;

use App\Jobs\SendCampaignLeadJob;
use App\Models\CampaignLead;
use Illuminate\Support\Facades\DB;

class CampaignDeliverySchedulerService
{
    /**
     * Process all due campaign leads across running campaigns.
     *
     * Returns the total number of campaign leads dispatched in this execution cycle.
     */
    public function processDueLeads(int $chunkSize = 100, ?int $maxBatchLimit = null): int
    {
        $dispatchedCount = 0;
        $maxBatch = $maxBatchLimit ?? (int) config('campaign.scheduler_batch_size', 500);

        \App\Models\EmailCampaign::where('status', 'running')
            ->orderBy('id', 'desc')
            ->each(function ($campaign) use (&$dispatchedCount, $chunkSize, $maxBatch) {
                if ($dispatchedCount >= $maxBatch) {
                    return false;
                }

                // Calculate cumulative hourly limit across all active senders for this campaign
                $sendersPivots = $campaign->senders()
                    ->with('sender')
                    ->whereHas('sender', function ($query) {
                        $query->where('is_active', true);
                    })
                    ->get();

                $activeSendersCount = $sendersPivots->count();

                // If senders exist, calculate spacing interval so emails are spread evenly across the hour
                $totalHourlyCapacity = $sendersPivots->sum(function ($cs) {
                    return (int) ($cs->sender->hourly_limit ?? 20);
                });

                // Default interval: 3600 / totalHourlyCapacity (e.g. 3600 / 40 = 90 seconds spacing)
                $baseIntervalSeconds = ($totalHourlyCapacity > 0)
                    ? (int) max(15, floor(3600.0 / (float) $totalHourlyCapacity))
                    : 90;

                $batchIndex = 0;

                $campaign->leads()
                    ->due()
                    ->chunkById($chunkSize, function ($leads) use (&$dispatchedCount, $maxBatch, $baseIntervalSeconds, &$batchIndex) {
                        foreach ($leads as $lead) {
                            if ($dispatchedCount >= $maxBatch) {
                                return false;
                            }

                            // Add subtle +/- 10s random jitter for natural human-like variation
                            $staggerSeconds = ($batchIndex * $baseIntervalSeconds) + rand(-10, 10);
                            if ($staggerSeconds < 0) $staggerSeconds = 0;

                            if ($this->claimAndDispatch($lead->id, $staggerSeconds)) {
                                $dispatchedCount++;
                                $batchIndex++;
                            }
                        }
                    });
            });

        return $dispatchedCount;
    }

    /**
     * Atomically claim a due campaign lead and dispatch SendCampaignLeadJob with optional pacing delay.
     */
    public function claimAndDispatch(int $leadId, int $staggerSeconds = 0): bool
    {
        return DB::transaction(function () use ($leadId, $staggerSeconds) {
            $lead = CampaignLead::where('id', $leadId)
                ->due()
                ->lockForUpdate()
                ->first();

            if (!$lead) {
                return false;
            }

            // Verify parent campaign is still running
            if (!$lead->campaign || $lead->campaign->status !== 'running') {
                return false;
            }

            // Atomically mark lead as processing to prevent duplicate dispatches
            $lead->update([
                'status' => 'processing',
                'processing_started_at' => now(),
                'last_attempt_at' => now(),
            ]);

            $job = SendCampaignLeadJob::dispatch($lead->id)->onQueue('emails');

            if ($staggerSeconds > 0) {
                $job->delay(now()->addSeconds($staggerSeconds));
            }

            $job->afterCommit();

            return true;
        });
    }
}
