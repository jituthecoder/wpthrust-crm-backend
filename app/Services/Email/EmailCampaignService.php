<?php

namespace App\Services\Email;

use App\Models\CampaignLead;
use App\Models\CampaignSender;
use App\Models\EmailCampaign;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmailCampaignService
{
    /**
     * Create Campaign
     */
    public function create(array $data): EmailCampaign
    {
        return DB::transaction(function () use ($data) {

            /*
            |--------------------------------------------------------------------------
            | Create Campaign
            |--------------------------------------------------------------------------
            */

            $campaign = EmailCampaign::create([

                'name'              => $data['name'],

                'description'       => $data['description'] ?? null,

                'email_template_id' => $data['email_template_id'],

                'scheduled_at'      => $data['scheduled_at'] ?? null,

                'status'            => empty($data['scheduled_at'])
                                        ? 'draft'
                                        : 'scheduled',

                'created_by'        => Auth::id(),

            ]);

            /*
            |--------------------------------------------------------------------------
            | Campaign Senders
            |--------------------------------------------------------------------------
            */

            foreach ($data['senders'] as $senderId) {

                CampaignSender::create([

                    'email_campaign_id' => $campaign->id,

                    'email_sender_id'   => $senderId,

                    'priority'          => 1,

                    'weight'            => 1,

                    'daily_limit'       => null,

                    'hourly_limit'      => null,

                    'is_active'         => true,

                ]);

            }

            /*
            |--------------------------------------------------------------------------
            | Campaign Leads
            |--------------------------------------------------------------------------
            */

            foreach ($data['businesses'] as $businessId) {

                CampaignLead::create([

                    'email_campaign_id'         => $campaign->id,

                    'business_id'               => $businessId,

                    'email_template_version_id' => null,

                    'status'                    => 'pending',

                ]);

            }

            /*
            |--------------------------------------------------------------------------
            | Statistics
            |--------------------------------------------------------------------------
            */

            $campaign->update([

                'total_leads' => count($data['businesses'])

            ]);

            return $campaign->load([

                'template',

                'creator',

                'senders.sender',

                'leads.business',

            ]);

        });
    }

    /**
     * Update Campaign
     */
    public function update(
        EmailCampaign $campaign,
        array $data
    ): EmailCampaign {

        return DB::transaction(function () use (
            $campaign,
            $data
        ) {

            /*
            |--------------------------------------------------------------------------
            | Update Campaign
            |--------------------------------------------------------------------------
            */

            $campaign->update([

                'name'              => $data['name'],

                'description'       => $data['description'] ?? null,

                'email_template_id' => $data['email_template_id'],

                'scheduled_at'      => $data['scheduled_at'] ?? null,

            ]);

            /*
            |--------------------------------------------------------------------------
            | Remove Old Relations
            |--------------------------------------------------------------------------
            */

            CampaignSender::where(

                'email_campaign_id',
                $campaign->id

            )->delete();

            CampaignLead::where(

                'email_campaign_id',
                $campaign->id

            )->delete();

            /*
            |--------------------------------------------------------------------------
            | Add Senders
            |--------------------------------------------------------------------------
            */

            $senderRows = [];

                foreach ($data['senders'] as $senderId) {

                    $senderRows[] = [

                        'email_campaign_id' => $campaign->id,

                        'email_sender_id'   => $senderId,

                        'priority'          => 1,

                        'weight'            => 1,

                        'daily_limit'       => null,

                        'hourly_limit'      => null,

                        'is_active'         => true,

                        'created_at'        => now(),

                        'updated_at'        => now(),

                    ];

                }

                CampaignSender::insert($senderRows);

            /*
            |--------------------------------------------------------------------------
            | Add Businesses
            |--------------------------------------------------------------------------
            */

            $leadRows = [];

            foreach ($data['businesses'] as $businessId) {

                $leadRows[] = [

                    'email_campaign_id'         => $campaign->id,

                    'business_id'               => $businessId,

                    'email_template_version_id' => null,

                    'status'                    => 'pending',

                    'created_at'                => now(),

                    'updated_at'                => now(),

                ];

            }

            CampaignLead::insert($leadRows);

            $campaign->update([

                'total_leads' => count(
                    $data['businesses']
                )

            ]);

            return $campaign->load([

                'template',

                'creator',

                'senders.sender',

                'leads.business',

            ]);

        });

    }

    /**
     * Delete Campaign
     */
    public function delete(
        EmailCampaign $campaign
    ): void {

        $campaign->delete();

    }

}