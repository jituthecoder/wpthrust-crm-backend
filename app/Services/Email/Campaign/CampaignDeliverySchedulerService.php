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

                $campaign->leads()
                    ->due()
                    ->chunkById($chunkSize, function ($leads) use (&$dispatchedCount, $maxBatch) {
                        foreach ($leads as $lead) {
                            if ($dispatchedCount >= $maxBatch) {
                                return false;
                            }

                            if ($this->claimAndDispatch($lead->id)) {
                                $dispatchedCount++;
                            }
                        }
                    });
            });

        return $dispatchedCount;
    }

    /**
     * Atomically claim a due campaign lead and dispatch SendCampaignLeadJob.
     *
     * Prevents duplicate dispatches if multiple scheduler instances or workers execute concurrently.
     */
    public function claimAndDispatch(int $leadId): bool
    {
        return DB::transaction(function () use ($leadId) {
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

            SendCampaignLeadJob::dispatch($lead->id)->onQueue('emails')->afterCommit();

            return true;
        });
    }
}
