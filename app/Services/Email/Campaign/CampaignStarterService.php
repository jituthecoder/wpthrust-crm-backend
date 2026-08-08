<?php

namespace App\Services\Email\Campaign;

use App\Jobs\SendCampaignLeadJob;
use App\Models\EmailCampaign;
use Illuminate\Support\Facades\DB;
use Exception;

class CampaignStarterService
{
    /**
     * Start Campaign
     */
    public function start(EmailCampaign $campaign): EmailCampaign
    {
        return DB::transaction(function () use ($campaign) {

            /*
            |--------------------------------------------------------------------------
            | Prevent Duplicate Start
            |--------------------------------------------------------------------------
            */

            if ($campaign->status === 'running') {
                throw new Exception(
                    'Campaign is already running.'
                );
            }

            if ($campaign->status === 'completed') {
                throw new Exception(
                    'Campaign has already been completed.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Update Campaign Status
            |--------------------------------------------------------------------------
            */

            $campaign->update([
                'status'     => 'running',
                'started_at' => now(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Queue Pending Leads
            |--------------------------------------------------------------------------
            */

            $this->queuePendingLeads($campaign);

            /*
            |--------------------------------------------------------------------------
            | Return Campaign
            |--------------------------------------------------------------------------
            */

            return $campaign->fresh();
        });
    }

    /**
     * Resume Campaign
     */
    public function resume(EmailCampaign $campaign): EmailCampaign
    {
        return DB::transaction(function () use ($campaign) {

            /*
            |--------------------------------------------------------------------------
            | Validate Campaign Status
            |--------------------------------------------------------------------------
            */

            if ($campaign->status !== 'paused') {
                throw new Exception(
                    'Only a paused campaign can be resumed.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Update Campaign Status
            |--------------------------------------------------------------------------
            */

            $campaign->update([
                'status' => 'running',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Queue Pending Leads
            |--------------------------------------------------------------------------
            */

            $this->queuePendingLeads($campaign);

            /*
            |--------------------------------------------------------------------------
            | Return Campaign
            |--------------------------------------------------------------------------
            */

            return $campaign->fresh();
        });
    }

    /**
     * Queue Pending Campaign Leads
     */
    protected function queuePendingLeads(
        EmailCampaign $campaign
    ): void {

        $campaign->leads()
            ->where('status', 'pending')
            ->chunkById(100, function ($leads) {

                foreach ($leads as $lead) {

                    SendCampaignLeadJob::dispatch(
                        $lead->id
                    )->afterCommit();

                }

            });
    }
}