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
            | Prevent duplicate start
            |--------------------------------------------------------------------------
            */

            if ($campaign->status === 'running') {
                throw new Exception('Campaign is already running.');
            }

            if ($campaign->status === 'completed') {
                throw new Exception('Campaign has already been completed.');
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

            $campaign->leads()
                ->where('status', 'pending')
                ->chunk(100, function ($leads) {

                    foreach ($leads as $lead) {

                        SendCampaignLeadJob::dispatch(
                            $lead->id
                        );

                    }

                });

            return $campaign->fresh();

        });
    }
}