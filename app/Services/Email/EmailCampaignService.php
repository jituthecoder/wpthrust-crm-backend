<?php

namespace App\Services\Email;

use App\Models\Business;
use App\Models\CampaignLead;
use App\Models\CampaignSender;
use App\Models\EmailCampaign;
use App\Services\Email\Campaign\CampaignStarterService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Jobs\SendCampaignLeadJob;

class EmailCampaignService
{
    /**
     * Campaign Starter Service
     */
    protected CampaignStarterService $campaignStarter;

    /**
     * Constructor
     */
    public function __construct(
        CampaignStarterService $campaignStarter
    ) {
        $this->campaignStarter = $campaignStarter;
    }

    /**
     * Create Campaign
     */
    public function create(array $data): EmailCampaign
    {
        return DB::transaction(function () use ($data) {

            /*
            |--------------------------------------------------------------------------
            | Get Businesses With Email
            |--------------------------------------------------------------------------
            */

            $businesses = !empty($data['businesses'])
                ? Business::whereIn('id', $data['businesses'])
                    ->whereNotNull('email')
                    ->where('email', '!=', '')
                    ->get()
                : collect();

            /*
            |--------------------------------------------------------------------------
            | Create Campaign
            |--------------------------------------------------------------------------
            */

            $campaign = EmailCampaign::create([

                'organization_id' => Auth::user()?->organization_id ?? $data['organization_id'] ?? 1,

                'name' => $data['name'],

                'description' => $data['description'] ?? null,

                'email_template_id' => $data['email_template_id'],

                'scheduled_at' => $data['scheduled_at'] ?? null,

                'auto_sync_enabled' => $data['auto_sync_enabled'] ?? false,

                'auto_sync_criteria' => $data['auto_sync_criteria'] ?? null,

                'status' => empty($data['scheduled_at'])
                    ? 'draft'
                    : 'scheduled',

                'created_by' => Auth::id(),

            ]);

            // Save sequence steps if provided
            \App\Models\CampaignSequenceStep::where('email_campaign_id', $campaign->id)->delete();
            \App\Models\CampaignSequenceStep::create([
                'email_campaign_id' => $campaign->id,
                'step_number' => 1,
                'email_template_id' => $data['email_template_id'],
                'delay_days' => 0,
                'condition' => 'always',
            ]);

            if (!empty($data['sequence_steps']) && is_array($data['sequence_steps'])) {
                foreach ($data['sequence_steps'] as $idx => $stepData) {
                    $stepNum = $idx + 2; // Step 2, Step 3...
                    \App\Models\CampaignSequenceStep::create([
                        'email_campaign_id' => $campaign->id,
                        'step_number' => $stepNum,
                        'email_template_id' => $stepData['email_template_id'] ?? $data['email_template_id'],
                        'delay_days' => (int) ($stepData['delay_days'] ?? 2),
                        'delay_hours' => (int) ($stepData['delay_hours'] ?? 0),
                        'condition' => $stepData['condition'] ?? 'always',
                    ]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Campaign Senders
            |--------------------------------------------------------------------------
            */

            $senderRows = [];
            $now = now();
            foreach ($data['senders'] as $senderId) {
                $senderRows[] = [
                    'email_campaign_id' => $campaign->id,
                    'email_sender_id' => $senderId,
                    'priority' => 1,
                    'weight' => 1,
                    'daily_limit' => null,
                    'hourly_limit' => null,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            if (!empty($senderRows)) {
                CampaignSender::insert($senderRows);
            }

            /*
            |--------------------------------------------------------------------------
            | Campaign Leads
            |--------------------------------------------------------------------------
            */

            $leadRows = [];
            if (!empty($data['contact_list_id'])) {
                $contactListLeads = \App\Models\ContactListLead::where('contact_list_id', $data['contact_list_id'])
                    ->whereNotNull('email')
                    ->where('email', '!=', '')
                    ->get();

                foreach ($contactListLeads as $cLead) {
                    $leadRows[] = [
                        'email_campaign_id' => $campaign->id,
                        'business_id' => $cLead->business_id,
                        'contact_list_lead_id' => $cLead->id,
                        'status' => 'pending',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            } elseif (!empty($businesses)) {
                foreach ($businesses as $business) {
                    $leadRows[] = [
                        'email_campaign_id' => $campaign->id,
                        'business_id' => $business->id,
                        'contact_list_lead_id' => null,
                        'status' => 'pending',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            if (!empty($leadRows)) {
                foreach (array_chunk($leadRows, 500) as $chunk) {
                    CampaignLead::insert($chunk);
                }
            }

            $campaign->update([
                'total_leads' => CampaignLead::where('email_campaign_id', $campaign->id)->count(),
            ]);

            // Sync matching leads if auto_sync_enabled
            if ($campaign->auto_sync_enabled) {
                app(\App\Services\Email\Campaign\CampaignAutoSyncService::class)->syncAllMatchingLeads($campaign);
            }

            /*
            |--------------------------------------------------------------------------
            | Statistics
            |--------------------------------------------------------------------------
            */

            $campaign->update([
                'total_leads' => CampaignLead::where('email_campaign_id', $campaign->id)->count(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Return Campaign (omitting leads.business to avoid huge JSON payloads)
            |--------------------------------------------------------------------------
            */

            return $campaign->load([
                'template',
                'creator',
                'senders.sender',
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

                'name' => $data['name'],

                'description' => $data['description'] ?? null,

                'email_template_id' => $data['email_template_id'],

                'scheduled_at' => $data['scheduled_at'] ?? null,

                'auto_sync_enabled' => $data['auto_sync_enabled'] ?? false,

                'auto_sync_criteria' => $data['auto_sync_criteria'] ?? null,

            ]);

            // Save sequence steps if provided
            \App\Models\CampaignSequenceStep::where('email_campaign_id', $campaign->id)->delete();
            \App\Models\CampaignSequenceStep::create([
                'email_campaign_id' => $campaign->id,
                'step_number' => 1,
                'email_template_id' => $data['email_template_id'],
                'delay_days' => 0,
                'condition' => 'always',
            ]);

            if (!empty($data['sequence_steps']) && is_array($data['sequence_steps'])) {
                foreach ($data['sequence_steps'] as $idx => $stepData) {
                    $stepNum = $idx + 2;
                    \App\Models\CampaignSequenceStep::create([
                        'email_campaign_id' => $campaign->id,
                        'step_number' => $stepNum,
                        'email_template_id' => $stepData['email_template_id'] ?? $data['email_template_id'],
                        'delay_days' => (int) ($stepData['delay_days'] ?? 2),
                        'delay_hours' => (int) ($stepData['delay_hours'] ?? 0),
                        'condition' => $stepData['condition'] ?? 'always',
                    ]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Update Senders
            |--------------------------------------------------------------------------
            */

            if (isset($data['senders']) && is_array($data['senders'])) {
                CampaignSender::where('email_campaign_id', $campaign->id)->delete();
                $senderRows = [];
                foreach ($data['senders'] as $senderId) {
                    $senderRows[] = [
                        'email_campaign_id' => $campaign->id,
                        'email_sender_id' => $senderId,
                        'priority' => 1,
                        'weight' => 1,
                        'daily_limit' => null,
                        'hourly_limit' => null,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                if (!empty($senderRows)) {
                    CampaignSender::insert($senderRows);
                }

                // Reset leads whose sender was removed or that failed due to sender availability
                CampaignLead::where('email_campaign_id', $campaign->id)
                    ->where(function ($q) use ($data) {
                        $q->whereNotIn('email_sender_id', $data['senders'] ?? [])
                          ->orWhere('failure_reason', 'LIKE', '%email sender%');
                    })
                    ->update([
                        'email_sender_id' => null,
                        'status' => 'pending',
                        'failure_reason' => null,
                    ]);

                if ($campaign->status === 'paused') {
                    app(\App\Services\Email\Campaign\CampaignStarterService::class)->resume($campaign);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Add New Businesses If Provided (Preserving Existing Leads & Stats)
            |--------------------------------------------------------------------------
            */

            if (!empty($data['businesses']) && is_array($data['businesses'])) {
                $existingBusinessIds = CampaignLead::where('email_campaign_id', $campaign->id)
                    ->pluck('business_id')
                    ->toArray();

                $newBusinessIds = array_diff($data['businesses'], $existingBusinessIds);

                if (!empty($newBusinessIds)) {
                    $businesses = Business::whereIn('id', $newBusinessIds)
                        ->whereNotNull('email')
                        ->where('email', '!=', '')
                        ->get();

                    $leadRows = [];
                    foreach ($businesses as $business) {
                        $leadRows[] = [
                            'email_campaign_id' => $campaign->id,
                            'business_id' => $business->id,
                            'status' => 'pending',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    if (!empty($leadRows)) {
                        CampaignLead::insert($leadRows);
                    }
                }

                $updateData = [
                    'total_leads' => CampaignLead::where('email_campaign_id', $campaign->id)->count(),
                ];

                if ($campaign->status === 'completed' && !empty($newBusinessIds)) {
                    $updateData['status'] = 'running';
                }

                $campaign->update($updateData);
            }

            /*
            |--------------------------------------------------------------------------
            | Add Campaign Leads
            |--------------------------------------------------------------------------
            */

            $leadRows = [];

            /*
            |--------------------------------------------------------------------------
            | Return Campaign
            |--------------------------------------------------------------------------
            */

            return $campaign->fresh()->load([
                'template',
                'creator',
                'senders.sender',
                'leads.business',
            ]);
        });
    }


    /**
     * Assign Businesses to Campaign
     */
    public function assignLeads(
        EmailCampaign $campaign,
        array $businessIds
    ): EmailCampaign {

        return DB::transaction(function () use (
            $campaign,
            $businessIds
        ) {

            /*
            |--------------------------------------------------------------------------
            | Get Eligible Businesses
            |--------------------------------------------------------------------------
            | Only businesses with an email address can be added.
            |--------------------------------------------------------------------------
            */

            $businesses = Business::whereIn(
                'id',
                $businessIds
            )
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->get();

            /*
            |--------------------------------------------------------------------------
            | Existing Campaign Leads
            |--------------------------------------------------------------------------
            */

            $existingBusinessIds = CampaignLead::where(
                'email_campaign_id',
                $campaign->id
            )
                ->whereIn(
                    'business_id',
                    $businesses->pluck('id')
                )
                ->pluck('business_id')
                ->toArray();

            /*
            |--------------------------------------------------------------------------
            | Create New Campaign Leads
            |--------------------------------------------------------------------------
            */

            $leadRows = [];

            foreach ($businesses as $business) {

                // Already assigned to this campaign
                if (in_array(
                    $business->id,
                    $existingBusinessIds
                )) {
                    continue;
                }

                $leadRows[] = [

                    'email_campaign_id' =>
                        $campaign->id,

                    'business_id' =>
                        $business->id,

                    'email_template_version_id' =>
                        null,

                    'status' =>
                        'pending',

                    'created_at' =>
                        now(),

                    'updated_at' =>
                        now(),

                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Insert Leads
            |--------------------------------------------------------------------------
            */

            if (!empty($leadRows)) {
                foreach (array_chunk($leadRows, 500) as $chunk) {
                    CampaignLead::insert($chunk);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Update Campaign Statistics & Reactivate if Completed
            |--------------------------------------------------------------------------
            */

            $updateData = [
                'total_leads' => CampaignLead::where(
                    'email_campaign_id',
                    $campaign->id
                )->count(),
            ];

            if ($campaign->status === 'completed' && !empty($leadRows)) {
                $updateData['status'] = 'running';
            }

            $campaign->update($updateData);

            // Sync all matching existing leads from DB if auto-sync is enabled
            if ($campaign->auto_sync_enabled) {
                app(\App\Services\Email\Campaign\CampaignAutoSyncService::class)->syncAllMatchingLeads($campaign);
            }

            /*
            |--------------------------------------------------------------------------
            | Return Updated Campaign (omitting leads.business to avoid huge JSON payloads)
            |--------------------------------------------------------------------------
            */

            return $campaign->fresh()->load([
                'template',
                'creator',
                'senders.sender',
            ]);
        });
    }


    /**
     * Pause Campaign
     */
    public function pause(
        EmailCampaign $campaign
    ): EmailCampaign {

        /*
        |--------------------------------------------------------------------------
        | Validate Campaign Status
        |--------------------------------------------------------------------------
        */

        if ($campaign->status !== 'running') {

            throw new \Exception(
                'Only a running campaign can be paused.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Pause Campaign
        |--------------------------------------------------------------------------
        */

        $campaign->update([

            'status' => 'paused',

        ]);

        /*
        |--------------------------------------------------------------------------
        | Return Campaign
        |--------------------------------------------------------------------------
        */

        return $campaign->fresh()->load([

            'template',

            'creator',

            'senders.sender',

            'leads.business',

        ]);
    }


    /**
     * Resume Campaign
     */
    public function resume(
        EmailCampaign $campaign
    ): EmailCampaign {

        /*
        |--------------------------------------------------------------------------
        | Resume Through Campaign Starter
        |--------------------------------------------------------------------------
        |
        | CampaignStarterService will:
        |
        | 1. Validate paused status
        | 2. Change status to running
        | 3. Find pending leads
        | 4. Dispatch pending leads to queue
        |
        |--------------------------------------------------------------------------
        */

        $campaign = $this->campaignStarter->resume(
            $campaign
        );

        /*
        |--------------------------------------------------------------------------
        | Return Campaign With Relations
        |--------------------------------------------------------------------------
        */

        return $campaign->load([

            'template',

            'creator',

            'senders.sender',

            'leads.business',

        ]);
    }


    /**
     * Get Campaign Statistics
     */
    public function stats(
        EmailCampaign $campaign
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Lead Counts
        |--------------------------------------------------------------------------
        */

        $total = $campaign->leads()->count();

        $pending = $campaign->leads()
            ->where('status', 'pending')
            ->count();

        $processing = $campaign->leads()
            ->where('status', 'processing')
            ->count();

        $sent = $campaign->leads()
            ->whereIn('status', ['sent', 'opened', 'clicked'])
            ->count();

        $failed = $campaign->leads()
            ->where('status', 'failed')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Progress
        |--------------------------------------------------------------------------
        */

        $processed = $sent + $failed;

        $progress = $total > 0
            ? round(($processed / $total) * 100, 2)
            : 0;

        /*
        |--------------------------------------------------------------------------
        | Return Statistics
        |--------------------------------------------------------------------------
        */

        $openedCount = $campaign->leads()->whereNotNull('opened_at')->count();
        $clickedCount = $campaign->leads()->whereNotNull('clicked_at')->count();
        $unsubscribedCount = $campaign->leads()->where(function($q) {
            $q->where('status', 'unsubscribed')->orWhereNotNull('unsubscribed_at');
        })->count();

        $openRate = $sent > 0 ? round(($openedCount / $sent) * 100, 1) : 0.0;
        $clickRate = $sent > 0 ? round(($clickedCount / $sent) * 100, 1) : 0.0;
        $unsubscribeRate = $sent > 0 ? round(($unsubscribedCount / $sent) * 100, 1) : 0.0;

        return [

            'campaign_id' => $campaign->id,

            'status' => $campaign->status,

            'total_leads' => $total,

            'pending' => $pending,

            'processing' => $processing,

            'sent' => $sent,

            'failed' => $failed,

            'unsubscribed' => $unsubscribedCount,

            'unsubscribe_rate' => $unsubscribeRate,

            'processed' => $processed,

            'progress' => $progress,

            'opened_count' => $openedCount,

            'clicked_count' => $clickedCount,

            'open_rate' => $openRate,

            'click_rate' => $clickRate,

        ];
    }


    /**
     * Cancel Campaign
     */
    public function cancel(
        EmailCampaign $campaign
    ): EmailCampaign {
        return DB::transaction(function () use ($campaign) {
            $locked = EmailCampaign::where('id', $campaign->id)->lockForUpdate()->first();

            if (in_array($locked->status, ['completed', 'cancelled'], true)) {
                throw new \Exception(
                    "Cannot cancel a campaign with status '{$locked->status}'."
                );
            }

            $locked->update([
                'status' => 'cancelled',
            ]);

            return $locked->fresh()->load([
                'template',
                'creator',
                'senders.sender',
                'leads.business',
            ]);
        });
    }

    /**
     * Mark Campaign Completed If All Leads Are Processed
     */
    public function completeIfFinished(
        EmailCampaign $campaign
    ): bool {
        return DB::transaction(function () use ($campaign) {
            $lockedCampaign = EmailCampaign::where('id', $campaign->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedCampaign) {
                return false;
            }

            if ($lockedCampaign->status === 'completed') {
                return true;
            }

            if ($lockedCampaign->status === 'cancelled') {
                return false;
            }

            /*
            |--------------------------------------------------------------------------
            | Count Remaining Leads
            |--------------------------------------------------------------------------
            */

            $pending = $lockedCampaign->leads()
                ->where('status', 'pending')
                ->count();

            $processing = $lockedCampaign->leads()
                ->where('status', 'processing')
                ->count();

            /*
            |--------------------------------------------------------------------------
            | Campaign Still Has Work
            |--------------------------------------------------------------------------
            */

            if ($pending > 0 || $processing > 0) {
                return false;
            }

            /*
            |--------------------------------------------------------------------------
            | Make Sure Campaign Actually Has Leads
            |--------------------------------------------------------------------------
            */

            $total = $lockedCampaign->leads()->count();

            if ($total === 0) {
                return false;
            }

            /*
            |--------------------------------------------------------------------------
            | Mark Campaign Completed
            |--------------------------------------------------------------------------
            */

            $sent = $lockedCampaign->leads()->whereIn('status', ['sent', 'opened', 'clicked'])->count();
            $failed = $lockedCampaign->leads()->where('status', 'failed')->count();

            $lockedCampaign->update([
                'status' => 'completed',
                'total_leads' => $total,
                'sent_count' => $sent,
                'failed_count' => $failed,
                'completed_at' => now(),
            ]);

            return true;
        });
    }


    /**
     * Get Campaign Leads
     */
    public function leads(
        EmailCampaign $campaign,
        array $filters = []
    ) {
        $query = $campaign->leads()
            ->with([
                'business',
                'sender',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Status Filter
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['status'])) {
            $query->where(
                'status',
                $filters['status']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['search'])) {

            $search = trim($filters['search']);

            $query->whereHas('business', function ($q) use ($search) {

                $q->where('business_name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");

            });
        }

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $perPage = min(
            (int) ($filters['per_page'] ?? 20),
            100
        );

        return $query
            ->latest('id')
            ->paginate($perPage);
    }


    /**
     * Retry All Failed Campaign Leads
     */
    public function retryAllFailedLeads(
        EmailCampaign $campaign
    ): array {

        return DB::transaction(function () use ($campaign) {

            /*
            |--------------------------------------------------------------------------
            | Get Failed Leads Eligible For Retry
            |--------------------------------------------------------------------------
            */

            $leads = CampaignLead::where(
                'email_campaign_id',
                $campaign->id
            )
                ->where('status', 'failed')
                ->whereColumn('retry_count', '<', 'max_retry')
                ->get();

            /*
            |--------------------------------------------------------------------------
            | No Leads Available
            |--------------------------------------------------------------------------
            */

            if ($leads->isEmpty()) {

                return [
                    'queued' => 0,
                    'skipped' => CampaignLead::where(
                        'email_campaign_id',
                        $campaign->id
                    )
                        ->where('status', 'failed')
                        ->count(),
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Restore Campaign To Running State If Needed
            |--------------------------------------------------------------------------
            */

            if (
                $campaign->status === 'completed' ||
                $campaign->status === 'paused' ||
                $campaign->status === 'draft'
            ) {

                $campaign->update([
                    'status' => 'running',
                ]);

            }

            /*
            |--------------------------------------------------------------------------
            | Reset Failed Leads to Pending for Scheduler Processing
            |--------------------------------------------------------------------------
            */

            foreach ($leads as $lead) {

                $lead->update([

                    'status' => 'pending',

                    'failure_reason' => null,

                    'processing_started_at' => null,

                    'last_attempt_at' => null,

                    'scheduled_at' => now(),

                    'sent_at' => null,

                    'provider_message_id' => null,

                    'provider_thread_id' => null,

                ]);

            }

            /*
            |--------------------------------------------------------------------------
            | Return Statistics (Leads set to pending, picked up by Scheduler)
            |--------------------------------------------------------------------------
            */

            return [

                'queued' => $leads->count(),

                'skipped' => CampaignLead::where(
                    'email_campaign_id',
                    $campaign->id
                )
                    ->where('status', 'failed')
                    ->count(),

            ];

        });
    }

    /**
     * Retry Single Failed Campaign Lead
     */
    public function retryLead(
        EmailCampaign $campaign,
        CampaignLead $lead
    ): CampaignLead {
        if ($lead->email_campaign_id !== $campaign->id) {
            throw new \Exception('Campaign lead does not belong to specified campaign.');
        }

        $lead->update([
            'status' => 'pending',
            'failure_reason' => null,
            'processing_started_at' => null,
            'last_attempt_at' => null,
            'scheduled_at' => now(),
        ]);

        if (in_array($campaign->status, ['completed', 'paused', 'draft'])) {
            $campaign->update(['status' => 'running']);
        }

        return $lead->fresh();
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