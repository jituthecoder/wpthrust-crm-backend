<?php

namespace App\Services\Email\Campaign;

use App\Models\EmailCampaign;
use Illuminate\Support\Carbon;
use Exception;

class RateDistributionService
{
    /**
     * Apply random jitter to a base timestamp based on interval seconds and jitter percentage.
     *
     * Default jitter is +/- 20% of the baseline interval (e.g. for an 864s interval, +/- 172.8s).
     */
    public function applyJitter(
        Carbon $baseTime,
        float $intervalSeconds,
        ?float $jitterPercent = null
    ): Carbon {
        if ($intervalSeconds <= 0) {
            return $baseTime->copy();
        }

        $effectiveJitterPercent = $jitterPercent ?? config('campaign.jitter_percent', 0.20);
        $maxJitterSeconds = $intervalSeconds * $effectiveJitterPercent;

        // Generate pseudo-random multiplier between -1.0 and +1.0
        $randomMultiplier = (random_int(-10000, 10000) / 10000.0);
        $jitterOffsetSeconds = (int) round($randomMultiplier * $maxJitterSeconds);

        $jitteredTime = $baseTime->copy()->addSeconds($jitterOffsetSeconds);

        // Ensure jittered time does not fall into the past
        return $jitteredTime->isPast() ? now() : $jitteredTime;
    }

    /**
     * Distribute pending campaign leads across time with baseline pacing and random jitter.
     */
    public function distributeCampaignLeads(
        EmailCampaign $campaign,
        ?float $jitterPercent = null
    ): void {
        $effectiveJitterPercent = $jitterPercent ?? config('campaign.jitter_percent', 0.20);

        $campaignSenders = $campaign->senders()
            ->with('sender')
            ->whereHas('sender', function ($query) {
                $query->where('is_active', true);
            })
            ->get();

        if ($campaignSenders->isEmpty()) {
            throw new Exception(
                'No active email sender is available for this campaign.'
            );
        }

        $totalDailyLimit = $campaignSenders->sum(function ($cs) {
            return (int) ($cs->sender->daily_limit ?? 0);
        });

        if ($totalDailyLimit <= 0) {
            throw new Exception(
                'The selected email senders do not have a valid daily sending limit.'
            );
        }

        $baselineIntervalSeconds = 86400.0 / (float) $totalDailyLimit;
        $baseScheduleTime = now();
        $lastScheduledAt = $baseScheduleTime->copy();
        $senderIndex = 0;
        $leadIndex = 0;

        $campaign->leads()
            ->where('status', 'pending')
            ->whereNull('scheduled_at')
            ->chunkById(100, function ($leads) use (
                $campaignSenders,
                &$senderIndex,
                &$leadIndex,
                &$baseScheduleTime,
                &$lastScheduledAt,
                $baselineIntervalSeconds,
                $effectiveJitterPercent
            ) {
                foreach ($leads as $lead) {
                    $campaignSender = $campaignSenders[
                        $senderIndex % $campaignSenders->count()
                    ];
                    $senderIndex++;

                    // Calculate baseline position time
                    $leadBaseTime = $baseScheduleTime->copy()->addSeconds((int) round($leadIndex * $baselineIntervalSeconds));
                    $leadIndex++;

                    // Apply random jitter to baseline time
                    $jitteredScheduledAt = $this->applyJitter($leadBaseTime, $baselineIntervalSeconds, $effectiveJitterPercent);

                    // Enforce strict non-decreasing chronological order across sequential leads
                    if ($jitteredScheduledAt->lessThan($lastScheduledAt)) {
                        $jitteredScheduledAt = $lastScheduledAt->copy();
                    }
                    $lastScheduledAt = $jitteredScheduledAt->copy();

                    $lead->update([
                        'email_sender_id' => $campaignSender->sender_id,
                        'scheduled_at'    => $jitteredScheduledAt,
                    ]);
                }
            });
    }
}
