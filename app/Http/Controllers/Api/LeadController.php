<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\LeadActivity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeadController extends Controller
{
    /**
     * Assign leads to Sales Executive
     */
    public function assign(Request $request)
    {
        $request->validate([
            'business_ids' => 'required|array|min:1',
            'business_ids.*' => 'exists:businesses,id',
            'assigned_user_id' => 'required|exists:users,id',
        ]);

        $salesExecutive = User::where('id', $request->assigned_user_id)
            ->where('role', 'sales_executive')
            ->first();

        if (!$salesExecutive) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Sales Executive.'
            ], 422);
        }

        DB::beginTransaction();

        try {
            $businesses = Business::whereIn('id', $request->business_ids)->get();

            foreach ($businesses as $business) {
                $oldUser = $business->assigned_user_id;

                $business->update([
                    'assigned_user_id' => $salesExecutive->id,
                ]);

                LeadActivity::create([
                    'business_id' => $business->id,
                    'user_id' => $request->user()->id,
                    'activity_type' => 'assigned',
                    'status' => $business->lead_status,
                    'comment' => "Lead assigned to {$salesExecutive->name}",
                    'meta' => [
                        'previous_user_id' => $oldUser,
                        'assigned_user_id' => $salesExecutive->id,
                    ],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($businesses) . ' lead(s) assigned successfully.',
                'assigned_count' => count($businesses),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * My Assigned Leads
     */
    public function myLeads(Request $request)
    {
        $query = Business::with([
            'audit',
            'assignedUser'
        ]);

        // Search Filter
        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        // Status Filter
        if ($request->filled('status')) {
            $query->where('lead_status', $request->status);
        }

        // Category Filter
        if ($request->filled('category')) {
            $query->where('category', 'LIKE', "%{$request->category}%");
        }

        // Location Filter
        if ($request->filled('location')) {
            $location = trim($request->location);
            $query->where(function ($q) use ($location) {
                $q->where('city', 'LIKE', "%{$location}%")
                  ->orWhere('state', 'LIKE', "%{$location}%")
                  ->orWhere('country', 'LIKE', "%{$location}%")
                  ->orWhere('address', 'LIKE', "%{$location}%")
                  ->orWhere('zip_code', 'LIKE', "%{$location}%");
            });
        }

        // Lead Created Date Filter
        if ($request->filled('created_at')) {
            $query->whereDate('created_at', $request->created_at);
        }
        if ($request->filled('created_from')) {
            $query->whereDate('created_at', '>=', $request->created_from);
        }
        if ($request->filled('created_to')) {
            $query->whereDate('created_at', '<=', $request->created_to);
        }

        // Has Website Filter
        if ($request->filled('has_website')) {
            if ($request->has_website === 'yes') {
                $query->whereNotNull('website')
                      ->where('website', '!=', '')
                      ->where('website', '!=', '-')
                      ->whereRaw('LOWER(website) != ?', ['n/a']);
            } elseif ($request->has_website === 'no') {
                $query->where(function ($q) {
                    $q->whereNull('website')
                      ->orWhere('website', '')
                      ->orWhere('website', '-')
                      ->orWhereRaw('LOWER(website) = ?', ['n/a']);
                });
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Assigned User Filter (Bulletproof for Super Admin & Sales Execs)
        |--------------------------------------------------------------------------
        */

        if (
            $request->assigned_user_id === 'unassigned' ||
            $request->assigned === 'unassigned' ||
            $request->assigned === 'no'
        ) {
            $query->where(function ($q) {
                $q->whereNull('assigned_user_id')
                  ->orWhere('assigned_user_id', 0)
                  ->orWhere('assigned_user_id', '');
            });
        } elseif ($request->filled('assigned_user_id')) {
            $query->where('assigned_user_id', $request->assigned_user_id);
        } elseif (auth()->user()->role !== 'super_admin') {
            $query->where('assigned_user_id', auth()->id());
        }

        $perPage = (int) $request->get('per_page', 20);
        if ($perPage < 1) {
            $perPage = 20;
        }

        $leads = $query
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $leads
        ]);
    }

    /**
     * Update Call Result
     */
    public function call(Request $request, Business $business)
    {
        $request->validate([
            'status' => [
                'required',
                'in:new,interested,call_later,not_interested,didnt_pick,not_reachable,wrong_number,converted'
            ],
            'comment' => 'nullable|string|max:5000',
            'followup_date' => 'nullable|date',
        ]);

        $user = $request->user();

        // Authorization
        if (
            $user->role !== 'super_admin' &&
            $business->assigned_user_id !== $user->id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to update this lead.'
            ], 403);
        }

        DB::beginTransaction();

        try {
            $business->update([
                'lead_status' => $request->status,
                'call_attempts' => $business->call_attempts + 1,
                'last_called_at' => now(),
                'next_followup_at' => $request->followup_date,
                'is_called' => true,
            ]);

            LeadActivity::create([
                'business_id' => $business->id,
                'user_id' => $user->id,
                'activity_type' => 'call',
                'status' => $request->status,
                'comment' => $request->comment,
                'followup_date' => $request->followup_date,
                'meta' => [
                    'call_attempt' => $business->call_attempts,
                ],
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Call updated successfully.',
                'data' => $business->fresh([
                    'assignedUser',
                    'audit',
                    'activities.user'
                ]),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Business $business)
    {
        if (
            auth()->user()->role !== 'super_admin'
            &&
            $business->assigned_user_id != auth()->id()
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.'
            ], 403);
        }

        $business->load([
            'audit',
            'assignedUser',
            'activities.user'
        ]);

        return response()->json([
            'success' => true,
            'data' => $business
        ]);
    }
}