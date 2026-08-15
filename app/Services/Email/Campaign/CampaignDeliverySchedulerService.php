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

                $senderSelector = app(EmailSenderSelectorService::class);
                $senderNextAvailable = [];

                $campaign->leads()
                    ->due()
                    ->chunkById($chunkSize, function ($leads) use (&$dispatchedCount, $maxBatch, $senderSelector, &$senderNextAvailable) {
                        foreach ($leads as $lead) {
                            if ($dispatchedCount >= $maxBatch) {
                                return false;
                            }

                            // Determine which sender gets this lead via capacity-weighted selection
                            $selectedCampaignSender = $senderSelector->select($lead);
                            $sender = $selectedCampaignSender?->sender;

                            $staggerSeconds = 0;
                            if ($sender) {
                                $senderId = $sender->id;
                                $hourlyLimit = max(1, (int) ($sender->hourly_limit ?? 20));
                                
                                // Calculate per-sender interval: e.g. 30/hr -> 120s (2m), 60/hr -> 60s (1m)
                                $senderInterval = (int) floor(3600.0 / (float) $hourlyLimit);

                                if (!isset($senderNextAvailable[$senderId])) {
                                    $senderNextAvailable[$senderId] = now();
                                    $targetTime = now();
                                } else {
                                    $senderNextAvailable[$senderId] = $senderNextAvailable[$senderId]->copy()->addSeconds($senderInterval);
                                    $targetTime = $senderNextAvailable[$senderId];
                                }

                                // Calculate delay seconds from now with +/- 10s random jitter for natural variation
                                $baseDelay = (int) max(0, $targetTime->diffInSeconds(now(), false) * -1);
                                if ($baseDelay < 0) $baseDelay = 0;

                                $jitter = rand(-10, 10);
                                $staggerSeconds = max(0, $baseDelay + $jitter);
                            }

                            if ($this->claimAndDispatch($lead->id, $staggerSeconds)) {
                                $dispatchedCount++;
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
