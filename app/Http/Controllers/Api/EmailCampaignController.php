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
     * Campaign List
     */
    public function index(Request $request)
    {
        $query = EmailCampaign::with([
            'template',
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
     * Assign Businesses to Campaign
     */
    public function assignLeads(
        AssignCampaignLeadsRequest $request,
        EmailCampaign $emailCampaign
    ) {

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

        return response()->json([

            'success' => true,

            'data' => $emailCampaign->load([

                'template.currentVersion',

                'creator',

                'senders.sender',

                'leads.business',

            ])

        ]);
    }

    /**
     * Campaign Statistics
     */
    public function stats(
        EmailCampaign $emailCampaign
    ) {

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
        $filters = $request->validate([

            'status' => [
                'nullable',
                'in:pending,processing,sent,failed',
            ],

            'search' => [
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
        EmailCampaign $emailCampaign
    ) {

        $result = $this->service->retryAllFailedLeads(
            $emailCampaign
        );

        return response()->json([

            'success' => true,

            'message' => $result['queued'] > 0
                ? 'Failed campaign leads queued for retry.'
                : 'No failed leads available for retry.',

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
     * Delete Campaign
     */
    public function destroy(
        EmailCampaign $emailCampaign
    ) {

        $this->service->delete(
            $emailCampaign
        );

        return response()->json([

            'success' => true,

            'message' => 'Campaign deleted successfully.',

        ]);
    }
}