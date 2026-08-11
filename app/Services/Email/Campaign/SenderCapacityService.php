<?php

namespace App\Services\Email\Campaign;

use App\Models\EmailSender;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SenderCapacityService
{
    /**
     * Calculate dynamic baseline pacing interval in seconds for a sender.
     *
     * Strict limit rule: The stricter constraint (larger interval) between daily and hourly limits wins.
     * Division-by-zero protection: If limit is null or <= 0, interval is 0.0 seconds.
     */
    public function getBaselineIntervalSeconds(EmailSender $sender): float
    {
        $dailyInterval = ($sender->daily_limit && $sender->daily_limit > 0)
            ? 86400.0 / (float) $sender->daily_limit
            : 0.0;

        $hourlyInterval = ($sender->hourly_limit && $sender->hourly_limit > 0)
            ? 3600.0 / (float) $sender->hourly_limit
            : 0.0;

        return max($dailyInterval, $hourlyInterval);
    }

    /**
     * Check if a sender currently has capacity to reserve for another email.
     */
    public function canReserve(EmailSender $sender): bool
    {
        if (!$sender->is_active) {
            return false;
        }

        // Clone/evaluate in memory for soft check
        $virtualSender = clone $sender;
        $this->checkAndApplyWindowResets($virtualSender);

        if ($virtualSender->daily_limit && $virtualSender->daily_limit > 0) {
            if ($virtualSender->reserved_today >= $virtualSender->daily_limit) {
                return false;
            }
        }

        if ($virtualSender->hourly_limit && $virtualSender->hourly_limit > 0) {
            if ($virtualSender->reserved_this_hour >= $virtualSender->hourly_limit) {
                return false;
            }
        }

        return true;
    }

    /**
     * Reserve capacity for a sender with atomic row locking.
     */
    public function reserveCapacity(EmailSender $sender): bool
    {
        return DB::transaction(function () use ($sender) {
            $lockedSender = EmailSender::where('id', $sender->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedSender || !$lockedSender->is_active) {
                return false;
            }

            $this->checkAndApplyWindowResets($lockedSender);

            if ($lockedSender->daily_limit && $lockedSender->daily_limit > 0) {
                if ($lockedSender->reserved_today >= $lockedSender->daily_limit) {
                    return false;
                }
            }

            if ($lockedSender->hourly_limit && $lockedSender->hourly_limit > 0) {
                if ($lockedSender->reserved_this_hour >= $lockedSender->hourly_limit) {
                    return false;
                }
            }

            $lockedSender->reserved_today += 1;
            $lockedSender->reserved_this_hour += 1;
            $lockedSender->last_reserved_at = now();
            $lockedSender->save();

            // Sync original model instance state
            $sender->fill($lockedSender->toArray());

            return true;
        });
    }

    /**
     * Release reserved capacity when a send attempt fails before delivery.
     */
    public function releaseCapacity(EmailSender $sender): void
    {
        DB::transaction(function () use ($sender) {
            $lockedSender = EmailSender::where('id', $sender->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedSender) {
                return;
            }

            $this->checkAndApplyWindowResets($lockedSender);

            $lockedSender->reserved_today = max(0, $lockedSender->reserved_today - 1);
            $lockedSender->reserved_this_hour = max(0, $lockedSender->reserved_this_hour - 1);
            $lockedSender->save();

            $sender->fill($lockedSender->toArray());
        });
    }

    /**
     * Record actual successful delivery statistics.
     */
    public function recordSendSuccess(EmailSender $sender): void
    {
        DB::transaction(function () use ($sender) {
            $lockedSender = EmailSender::where('id', $sender->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedSender) {
                return;
            }

            $this->checkAndApplyWindowResets($lockedSender);

            $lockedSender->sent_today += 1;
            $lockedSender->sent_this_hour += 1;
            $lockedSender->last_sent_at = now();
            $lockedSender->save();

            $sender->fill($lockedSender->toArray());
        });
    }

    /**
     * Determine the next safe/eligible send time for a sender.
     */
    public function getNextAvailableAt(EmailSender $sender): Carbon
    {
        if (!$sender->is_active) {
            return now()->addYears(100);
        }

        $virtualSender = clone $sender;
        $this->checkAndApplyWindowResets($virtualSender);

        $now = now();
        $nextAvailable = $now->copy();

        // Daily limit exhaustion check
        if ($virtualSender->daily_limit && $virtualSender->daily_limit > 0) {
            if ($virtualSender->reserved_today >= $virtualSender->daily_limit) {
                $dailyResetTime = $now->copy()->addDay()->startOfDay();
                if ($dailyResetTime->greaterThan($nextAvailable)) {
                    $nextAvailable = $dailyResetTime;
                }
            }
        }

        // Hourly limit exhaustion check
        if ($virtualSender->hourly_limit && $virtualSender->hourly_limit > 0) {
            if ($virtualSender->reserved_this_hour >= $virtualSender->hourly_limit) {
                $hourlyResetTime = $now->copy()->addHour()->startOfHour();
                if ($hourlyResetTime->greaterThan($nextAvailable)) {
                    $nextAvailable = $hourlyResetTime;
                }
            }
        }

        // Pacing interval check
        $intervalSeconds = $this->getBaselineIntervalSeconds($virtualSender);
        if ($intervalSeconds > 0 && $virtualSender->last_reserved_at) {
            $pacingNext = $virtualSender->last_reserved_at->copy()->addSeconds((int) ceil($intervalSeconds));
            if ($pacingNext->greaterThan($nextAvailable)) {
                $nextAvailable = $pacingNext;
            }
        }

        return $nextAvailable;
    }

    /**
     * Check and reset daily/hourly usage counters when boundary windows expire.
     */
    public function checkAndApplyWindowResets(EmailSender $sender): void
    {
        $now = now();

        // Daily window reset (resets at start of new day)
        $startOfDay = $now->copy()->startOfDay();
        if (is_null($sender->last_daily_reset_at) || $sender->last_daily_reset_at->lessThan($startOfDay)) {
            $sender->sent_today = 0;
            $sender->reserved_today = 0;
            $sender->last_daily_reset_at = $now;
        }

        // Hourly window reset (resets at start of new hour)
        $startOfHour = $now->copy()->startOfHour();
        if (is_null($sender->last_hourly_reset_at) || $sender->last_hourly_reset_at->lessThan($startOfHour)) {
            $sender->sent_this_hour = 0;
            $sender->reserved_this_hour = 0;
            $sender->last_hourly_reset_at = $now;
        }
    }
}
