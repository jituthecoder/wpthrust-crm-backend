<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmailCampaignRequest;
use App\Http\Requests\AssignCampaignLeadsRequest;
use App\Models\EmailCampaign;
use App\Services\Email\EmailCampaignService;
use Illuminate\Http\Request;
use App\Services\Email\Campaign\CampaignStarterService;
use App\Models\CampaignLead;

class EmailCampaignController extends Controller
{
    protected EmailCampaignService $service;

    protected CampaignStarterService $campaignStarter;

    public function __construct(
        EmailCampaignService $service,
        CampaignStarterService $campaignStarter
    ) {
        $this->service = $service;

        $this->campaignStarter = $campaignStarter;
    }

    /**
     * Tenant Authorization Guard
     */
    protected function authorizeTenant(EmailCampaign $campaign): void
    {
        $user = auth()->user();
        if ($user && $campaign->organization_id !== null && $campaign->organization_id !== $user->organization_id) {
            abort(403, 'Unauthorized tenant access to campaign.');
        }
    }

    /**
     * Campaign List
     */
    public function index(Request $request)
    {
        $orgId = $request->user()->organization_id;

        $query = EmailCampaign::where(function ($q) use ($orgId) {
            $q->where('organization_id', $orgId);
            if ($orgId == 1) {
                $q->orWhereNull('organization_id');
            }
        })
            ->with([
                'template',
                'sequenceSteps.template',
                'senders.sender',
                'creator',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $query->where(function ($q) use ($request) {

                $q->where(
                    'name',
                    'LIKE',
                    '%' . trim($request->search) . '%'
                );

            });
        }

        /*
        |--------------------------------------------------------------------------
        | Status Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('status')) {

            $query->where(
                'status',
                $request->status
            );
        }

        return response()->json([

            'success' => true,

            'data' => $query
                ->latest()
                ->paginate(20),

        ]);
    }

    /**
     * Create Campaign
     */
    public function store(
        EmailCampaignRequest $request
    ) {

        $campaign = $this->service->create(
            $request->validated()
        );

        return response()->json([

            'success' => true,

            'message' => 'Campaign created successfully.',

            'data' => $campaign,

        ], 201);
    }

    /**
     * Start Campaign
     */
    public function start(
        EmailCampaign $emailCampaign
    ) {
        $this->authorizeTenant($emailCampaign);

        $campaign = $this->campaignStarter->start(
            $emailCampaign
        );

        return response()->json([

            'success' => true,

            'message' => 'Campaign started successfully.',

            'data' => $campaign,

        ]);
    }

    /**
     * Pause Campaign
     */
    public function pause(
        EmailCampaign $emailCampaign
    ) {
        $this->authorizeTenant($emailCampaign);

        $campaign = $this->service->pause(
            $emailCampaign
        );

        return response()->json([

            'success' => true,

            'message' => 'Campaign paused successfully.',

            'data' => $campaign,

        ]);
    }

    /**
     * Resume Campaign
     */
    public function resume(
        EmailCampaign $emailCampaign
    ) {
        $this->authorizeTenant($emailCampaign);

        $campaign = $this->service->resume(
            $emailCampaign
        );

        return response()->json([

            'success' => true,

            'message' => 'Campaign resumed successfully.',

            'data' => $campaign,

        ]);
    }

    /**
     * Cancel Campaign
     */
    public function cancel(
        EmailCampaign $emailCampaign
    ) {
        $this->authorizeTenant($emailCampaign);

        $campaign = $this->service->cancel(
            $emailCampaign
        );

        return response()->json([

            'success' => true,

            'message' => 'Campaign cancelled successfully.',

            'data' => $campaign,

        ]);
    }

    /**
     * Assign Businesses to Campaign
     */
    public function assignLeads(
        AssignCampaignLeadsRequest $request,
        EmailCampaign $emailCampaign
    ) {
        $this->authorizeTenant($emailCampaign);

        $campaign = $this->service->assignLeads(
            $emailCampaign,
            $request->validated('businesses')
        );

        return response()->json([

            'success' => true,

            'message' => 'Businesses assigned to campaign successfully.',

            'data' => $campaign,

        ]);
    }

    /**
     * Campaign Details
     */
    public function show(
        EmailCampaign $emailCampaign
    ) {
        $this->authorizeTenant($emailCampaign);

        return response()->json([

            'success' => true,

            'data' => $emailCampaign->load([

                'template.currentVersion',

                'sequenceSteps.template',

                'creator',

                'senders.sender',

                'leads.business',

                'leads.stepExecutions',

            ])

        ]);
    }

    /**
     * Campaign Statistics
     */
    public function stats(
        EmailCampaign $emailCampaign
    ) {
        $this->authorizeTenant($emailCampaign);

        $stats = $this->service->stats(
            $emailCampaign
        );

        return response()->json([

            'success' => true,

            'data' => $stats,

        ]);
    }

    /**
     * Campaign Leads
     */
    public function leads(
        Request $request,
        EmailCampaign $emailCampaign
    ) {
        $this->authorizeTenant($emailCampaign);

        $filters = $request->validate([

            'status' => [
                'nullable',
                'in:pending,processing,sent,failed,unsubscribed',
            ],

            'search' => [
                'nullable',
                'string',
                'max:255',
            ],

            'error_search' => [
                'nullable',
                'string',
                'max:255',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],

        ]);

        $leads = $this->service->leads(
            $emailCampaign,
            $filters
        );

        return response()->json([

            'success' => true,

            'data' => $leads,

        ]);
    }

    /**
     * Retry Failed Campaign Lead
     */
    public function retryLead(
        EmailCampaign $emailCampaign,
        CampaignLead $campaignLead
    ) {
        $this->authorizeTenant($emailCampaign);

        if ($campaignLead->email_campaign_id !== $emailCampaign->id) {
            abort(404, 'Campaign lead does not belong to specified campaign.');
        }

        $lead = $this->service->retryLead(
            $emailCampaign,
            $campaignLead
        );

        return response()->json([

            'success' => true,

            'message' => 'Campaign lead queued for retry.',

            'data' => $lead,

        ]);
    }

    /**
     * Retry All Failed Campaign Leads
     */
    public function retryAllFailedLeads(
        Request $request,
        EmailCampaign $emailCampaign
    ) {
        $this->authorizeTenant($emailCampaign);

        $errorFilter = $request->input('error_filter');
        $leadIds = $request->input('lead_ids');

        $result = $this->service->retryAllFailedLeads(
            $emailCampaign,
            $errorFilter,
            is_array($leadIds) ? $leadIds : null
        );

        return response()->json([

            'success' => true,

            'message' => $result['queued'] > 0
                ? "{$result['queued']} failed lead(s) queued for retry."
                : 'No failed leads matched criteria for retry.',

            'data' => [

                'campaign_id' => $emailCampaign->id,

                'queued' => $result['queued'],

                'skipped' => $result['skipped'],

            ],

        ]);
    }

    /**
     * Update Campaign
     */
    public function update(
        EmailCampaignRequest $request,
        EmailCampaign $emailCampaign
    ) {
        $this->authorizeTenant($emailCampaign);

        $campaign = $this->service->update(

            $emailCampaign,

            $request->validated()

        );

        return response()->json([

            'success' => true,

            'message' => 'Campaign updated successfully.',

            'data' => $campaign,

        ]);
    }

    /**
     * Remove Lead from Campaign
     */
    public function removeLead(
        EmailCampaign $emailCampaign,
        CampaignLead $campaignLead
    ) {
        $this->authorizeTenant($emailCampaign);

        if ($campaignLead->email_campaign_id !== $emailCampaign->id) {
            return response()->json([
                'success' => false,
                'message' => 'Lead does not belong to this campaign.',
            ], 404);
        }

        $campaignLead->delete();
        $emailCampaign->update([
            'total_leads' => $emailCampaign->leads()->count(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lead removed from campaign successfully.',
        ]);
    }

    /**
     * Import Leads from Contact List to Campaign
     */
    public function importContactList(
        EmailCampaign $emailCampaign,
        Request $request
    ) {
        $this->authorizeTenant($emailCampaign);

        $request->validate([
            'contact_list_id' => 'required|exists:contact_lists,id',
        ]);

        $contactListLeads = \App\Models\ContactListLead::where('contact_list_id', $contactList->id)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get();

        if ($contactListLeads->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => "The contact list '{$contactList->name}' contains no contacts with an email address.",
            ], 400);
        }

        $existingContactListLeadIds = CampaignLead::where('email_campaign_id', $emailCampaign->id)
            ->whereNotNull('contact_list_lead_id')
            ->pluck('contact_list_lead_id')
            ->toArray();

        $leadRows = [];
        $now = now();
        $addedCount = 0;

        foreach ($contactListLeads as $cLead) {
            if (in_array($cLead->id, $existingContactListLeadIds)) {
                continue;
            }

            $leadRows[] = [
                'email_campaign_id' => $emailCampaign->id,
                'business_id' => $cLead->business_id,
                'contact_list_lead_id' => $cLead->id,
                'status' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $addedCount++;
        }

        if (!empty($leadRows)) {
            foreach (array_chunk($leadRows, 500) as $chunk) {
                CampaignLead::insert($chunk);
            }
        }

        $emailCampaign->update([
            'total_leads' => CampaignLead::where('email_campaign_id', $emailCampaign->id)->count(),
        ]);

        if ($emailCampaign->status === 'completed' && $addedCount > 0) {
            $emailCampaign->update(['status' => 'running']);
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully imported {$addedCount} contact(s) from '{$contactList->name}' into campaign.",
            'data' => [
                'added_count' => $addedCount,
                'total_leads' => $emailCampaign->fresh()->total_leads,
            ],
        ]);
    }

    /**
     * Trigger Manual Sync of Matching Leads for Campaign
     */
    public function syncLeads(
        EmailCampaign $emailCampaign
    ) {
        $this->authorizeTenant($emailCampaign);

        $autoSyncService = app(\App\Services\Email\Campaign\CampaignAutoSyncService::class);
        $addedCount = $autoSyncService->syncAllMatchingLeads($emailCampaign);

        return response()->json([
            'success' => true,
            'message' => "Synced {$addedCount} matching leads to campaign successfully.",
            'data' => [
                'added_count' => $addedCount,
                'total_leads' => $emailCampaign->fresh()->leads()->count(),
            ],
        ]);
    }

    /**
     * Delete Campaign
     */
    public function destroy(
        EmailCampaign $emailCampaign
    ) {
        $this->authorizeTenant($emailCampaign);

        $this->service->delete(
            $emailCampaign
        );

        return response()->json([

            'success' => true,

            'message' => 'Campaign deleted successfully.',

        ]);
    }
}