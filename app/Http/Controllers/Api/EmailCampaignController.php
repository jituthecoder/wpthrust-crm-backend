<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmailCampaignRequest;
use App\Models\EmailCampaign;
use App\Services\Email\EmailCampaignService;
use Illuminate\Http\Request;
use App\Services\Email\Campaign\CampaignStarterService;

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