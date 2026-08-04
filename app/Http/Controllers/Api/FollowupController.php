<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use Illuminate\Http\Request;

class FollowupController extends Controller
{
    /**
     * Today's Followups
     */
    public function today(Request $request)
    {
        $query = Business::with(['audit', 'assignedUser'])
            ->whereDate('next_followup_at', today());

        if ($request->user()->role === 'sales_executive') {
            $query->where('assigned_user_id', $request->user()->id);
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderBy('next_followup_at')->paginate(20)
        ]);
    }

    /**
     * Upcoming Followups
     */
    public function upcoming(Request $request)
    {
        $query = Business::with(['audit', 'assignedUser'])
            ->whereDate('next_followup_at', '>', today());

        if ($request->user()->role === 'sales_executive') {
            $query->where('assigned_user_id', $request->user()->id);
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderBy('next_followup_at')->paginate(20)
        ]);
    }

    /**
     * Overdue Followups
     */
    public function overdue(Request $request)
    {
        $query = Business::with(['audit', 'assignedUser'])
            ->whereDate('next_followup_at', '<', today());

        if ($request->user()->role === 'sales_executive') {
            $query->where('assigned_user_id', $request->user()->id);
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderBy('next_followup_at')->paginate(20)
        ]);
    }
}