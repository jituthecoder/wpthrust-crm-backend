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
                  ->orWhere('website', 'LIKE', "%{$search}%")
                  ->orWhere('city', 'LIKE', "%{$search}%");
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Lead Status Filter
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
        | Country Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('country')) {
            $query->where('country', 'LIKE', "%{$request->country}%");
        }

        /*
        |--------------------------------------------------------------------------
        | PageSpeed Score Filter (PSI)
        |--------------------------------------------------------------------------
        */

        if ($request->filled('psi_filter')) {
            $filter = $request->psi_filter;
            if ($filter === 'less_50') {
                $query->whereHas('audit', function ($q) {
                    $q->where('mobile_pagespeed', '>', 0)->where('mobile_pagespeed', '<', 50);
                });
            } elseif ($filter === 'less_90') {
                $query->whereHas('audit', function ($q) {
                    $q->where('mobile_pagespeed', '>', 0)->where('mobile_pagespeed', '<', 90);
                });
            } elseif ($filter === 'good_90') {
                $query->whereHas('audit', function ($q) {
                    $q->where('mobile_pagespeed', '>=', 90);
                });
            } elseif ($filter === 'not_audited') {
                $query->where(function ($q) {
                    $q->whereDoesntHave('audit')
                      ->orWhereHas('audit', function ($sq) {
                          $sq->whereNull('mobile_pagespeed')->orWhere('mobile_pagespeed', 0);
                      });
                });
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Has Screenshot Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('has_screenshot')) {
            if ($request->has_screenshot === 'yes') {
                $query->whereHas('audit', function ($q) {
                    $q->whereNotNull('mobile_screenshot_path')->where('mobile_screenshot_path', '!=', '');
                });
            } elseif ($request->has_screenshot === 'no') {
                $query->where(function ($q) {
                    $q->whereDoesntHave('audit')
                      ->orWhereHas('audit', function ($sq) {
                          $sq->whereNull('mobile_screenshot_path')->orWhere('mobile_screenshot_path', '');
                      });
                });
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Has Website Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('has_website')) {
            if ($request->has_website === 'yes') {
                $query->whereNotNull('website')
                      ->where('website', '!=', '')
                      ->where('website', '!=', '-');
            } elseif ($request->has_website === 'no') {
                $query->where(function ($q) {
                    $q->whereNull('website')
                      ->orWhere('website', '')
                      ->orWhere('website', '-');
                });
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Assigned Filter
        |--------------------------------------------------------------------------
        */

        if (
            $request->assigned_user_id === 'unassigned' ||
            $request->assigned === 'no' ||
            $request->assigned === 'unassigned'
        ) {
            $query->where(function ($q) {
                $q->whereNull('assigned_user_id')
                  ->orWhere('assigned_user_id', 0)
                  ->orWhere('assigned_user_id', '');
            });
        } elseif ($request->assigned === 'yes') {
            $query->whereNotNull('assigned_user_id')->where('assigned_user_id', '!=', 0);
        } elseif ($request->filled('assigned_user_id')) {
            $query->where('assigned_user_id', $request->assigned_user_id);
        }

        /*
        |--------------------------------------------------------------------------
        | Sorting & Pagination
        |--------------------------------------------------------------------------
        */

        $allowedSorts = ['business_name', 'category', 'lead_status', 'created_at'];
        $sort = $request->get('sort', 'id');
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'id';
        }

        $direction = strtolower($request->get('direction', 'desc'));
        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'desc';
        }

        $query->orderBy($sort, $direction);

        $perPage = (int) $request->get('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100, 250, 500])) {
            $perPage = 20;
        }

        $businesses = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $businesses,
        ]);
    }

    public function categories()
    {
        $categories = Business::whereNotNull('category')
            ->where('category', '!=', '')
            ->where('category', '!=', '-')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    public function countries()
    {
        $countries = Business::whereNotNull('country')
            ->where('country', '!=', '')
            ->where('country', '!=', '-')
            ->distinct()
            ->orderBy('country')
            ->pluck('country');

        return response()->json([
            'success' => true,
            'data' => $countries,
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

    public function fetchPsi(Business $business)
    {
        if (empty($business->website)) {
            return response()->json([
                'success' => false,
                'message' => 'This business does not have a website URL.',
            ], 422);
        }

        \App\Jobs\FetchBusinessPsiJob::dispatch($business);

        return response()->json([
            'success' => true,
            'message' => 'PageSpeed Insights background audit dispatched successfully.',
        ]);
    }
}