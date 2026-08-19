<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Business::query();

        // Sales executives only see their own leads
        if ($user->role === 'sales_executive') {
            $query->where('assigned_user_id', $user->id);
        }

        $today = Carbon::today();

        return response()->json([
            'success' => true,

            'data' => [

                'total_businesses' => (clone $query)->count(),

                'assigned' => (clone $query)
                    ->whereNotNull('assigned_user_id')
                    ->count(),

                'unassigned' => (clone $query)
                    ->whereNull('assigned_user_id')
                    ->count(),

                'interested' => (clone $query)
                    ->where('lead_status', 'interested')
                    ->count(),

                'call_later' => (clone $query)
                    ->where('lead_status', 'call_later')
                    ->count(),

                'not_interested' => (clone $query)
                    ->where('lead_status', 'not_interested')
                    ->count(),

                'didnt_pick' => (clone $query)
                    ->where('lead_status', 'didnt_pick')
                    ->count(),

                'not_reachable' => (clone $query)
                    ->where('lead_status', 'not_reachable')
                    ->count(),

                'converted' => (clone $query)
                    ->where('lead_status', 'converted')
                    ->count(),

                'bounced_leads' => (clone $query)
                    ->where('lead_status', 'bounced')
                    ->count(),

                'bounced_emails' => \App\Models\CampaignLead::where('status', 'bounced')->count(),

                'today_calls' => (clone $query)
                    ->whereDate('last_called_at', $today)
                    ->count(),

                'today_followups' => (clone $query)
                    ->whereDate('next_followup_at', $today)
                    ->count(),

                'pending_followups' => (clone $query)
                    ->whereDate('next_followup_at', '<=', now())
                    ->whereNotNull('next_followup_at')
                    ->count(),

            ]
        ]);
    }
}