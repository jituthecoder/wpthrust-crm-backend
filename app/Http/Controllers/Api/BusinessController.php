<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use Illuminate\Http\Request;

class BusinessController extends Controller
{
    public function index(Request $request)
    {
        $query = Business::with([
            'audit',
            'assignedUser'
        ]);

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search = trim($request->search);

            $query->where(function ($q) use ($search) {

                $q->where('business_name', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('website', 'LIKE', "%{$search}%");

            });

        }

        /*
        |--------------------------------------------------------------------------
        | Status Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('status')) {

            $query->where('lead_status', $request->status);

        }

        /*
        |--------------------------------------------------------------------------
        | Category Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('category')) {

            $query->where('category', 'LIKE', "%{$request->category}%");

        }

        /*
        |--------------------------------------------------------------------------
        | Assigned Filter
        |--------------------------------------------------------------------------
        */

        if ($request->assigned === 'yes') {

            $query->whereNotNull('assigned_user_id');

        }

        if ($request->assigned === 'no') {

            $query->whereNull('assigned_user_id');

        }

        /*
        |--------------------------------------------------------------------------
        | Sorting
        |--------------------------------------------------------------------------
        */

        $allowedSorts = [

            'business_name',
            'category',
            'lead_status',
            'created_at',

        ];

        $sort = $request->get('sort', 'id');

        if (!in_array($sort, $allowedSorts)) {

            $sort = 'id';

        }

        $direction = strtolower($request->get('direction', 'desc'));

        if (!in_array($direction, ['asc', 'desc'])) {

            $direction = 'desc';

        }

        $query->orderBy($sort, $direction);

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $perPage = (int) $request->get('per_page', 20);

        if (!in_array($perPage, [10,20,50,100])) {

            $perPage = 20;

        }

        $businesses = $query->paginate($perPage);

        return response()->json([

            'success' => true,

            'data' => $businesses,

        ]);

    }


    public function show(Business $business)
    {
        $business->load([
            'audit',
            'assignedUser',
            'activities.user'
        ]);

        return response()->json([
            'success' => true,
            'data' => $business,
        ]);
    }
}