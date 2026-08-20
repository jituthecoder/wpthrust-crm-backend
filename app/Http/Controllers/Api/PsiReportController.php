<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessAudit;
use App\Jobs\FetchBusinessPsiJob;
use Illuminate\Http\Request;

class PsiReportController extends Controller
{
    /**
     * Executive PSI Statistics
     */
    public function stats()
    {
        $totalBusinesses = Business::count();
        $totalAudits = BusinessAudit::count();

        // 1. Both Score & Screenshot Available
        $bothAvailable = BusinessAudit::where(function ($q) {
            $q->whereNotNull('mobile_pagespeed')->orWhereNotNull('desktop_pagespeed');
        })->whereNotNull('mobile_screenshot_path')->where('mobile_screenshot_path', '!=', '')->count();

        // 2. Score Only (Missing Screenshot)
        $scoreOnly = BusinessAudit::where(function ($q) {
            $q->whereNotNull('mobile_pagespeed')->orWhereNotNull('desktop_pagespeed');
        })->where(function ($q) {
            $q->whereNull('mobile_screenshot_path')->orWhere('mobile_screenshot_path', '');
        })->count();

        // 3. Screenshot Only (Missing Score)
        $screenshotOnly = BusinessAudit::whereNull('mobile_pagespeed')
            ->whereNull('desktop_pagespeed')
            ->whereNotNull('mobile_screenshot_path')
            ->where('mobile_screenshot_path', '!=', '')
            ->count();

        // 4. Missing Both / Failed
        $missingBoth = BusinessAudit::whereNull('mobile_pagespeed')
            ->whereNull('desktop_pagespeed')
            ->where(function ($q) {
                $q->whereNull('mobile_screenshot_path')->orWhere('mobile_screenshot_path', '');
            })
            ->count();

        // 5. Explicit Failed / Error Status
        $failedCount = BusinessAudit::where('psi_status', 'failed')
            ->orWhereNotNull('psi_error_reason')
            ->count();

        // Averages
        $avgMobile = round(BusinessAudit::whereNotNull('mobile_pagespeed')->avg('mobile_pagespeed') ?? 0, 1);
        $avgDesktop = round(BusinessAudit::whereNotNull('desktop_pagespeed')->avg('desktop_pagespeed') ?? 0, 1);

        return response()->json([
            'success' => true,
            'data' => [
                'total_businesses' => $totalBusinesses,
                'total_audits' => $totalAudits,
                'both_available' => $bothAvailable,
                'score_only' => $scoreOnly,
                'screenshot_only' => $screenshotOnly,
                'missing_both' => $missingBoth,
                'failed_count' => $failedCount,
                'avg_mobile_pagespeed' => $avgMobile,
                'avg_desktop_pagespeed' => $avgDesktop,
            ],
        ]);
    }

    /**
     * Paginated PSI Reports List
     */
    public function index(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 20), 100);
        $availability = $request->input('availability', 'all');
        $search = trim($request->input('search', ''));
        $sortBy = $request->input('sort_by', 'latest');

        $query = BusinessAudit::with('business');

        // Filter by availability condition
        switch ($availability) {
            case 'both':
                $query->where(function ($q) {
                    $q->whereNotNull('mobile_pagespeed')->orWhereNotNull('desktop_pagespeed');
                })->whereNotNull('mobile_screenshot_path')->where('mobile_screenshot_path', '!=', '');
                break;

            case 'score_only':
                $query->where(function ($q) {
                    $q->whereNotNull('mobile_pagespeed')->orWhereNotNull('desktop_pagespeed');
                })->where(function ($q) {
                    $q->whereNull('mobile_screenshot_path')->orWhere('mobile_screenshot_path', '');
                });
                break;

            case 'screenshot_only':
                $query->whereNull('mobile_pagespeed')
                    ->whereNull('desktop_pagespeed')
                    ->whereNotNull('mobile_screenshot_path')
                    ->where('mobile_screenshot_path', '!=', '');
                break;

            case 'missing_both':
                $query->whereNull('mobile_pagespeed')
                    ->whereNull('desktop_pagespeed')
                    ->where(function ($q) {
                        $q->whereNull('mobile_screenshot_path')->orWhere('mobile_screenshot_path', '');
                    });
                break;

            case 'failed':
                $query->where(function ($q) {
                    $q->where('psi_status', 'failed')
                      ->orWhereNotNull('psi_error_reason');
                });
                break;

            case 'processing':
                $query->where('psi_status', 'processing');
                break;

            default: // all
                break;
        }

        // Search in Business Name, Domain, or Email
        if ($search) {
            $query->whereHas('business', function ($q) use ($search) {
                $q->where('business_name', 'LIKE', "%{$search}%")
                  ->orWhere('domain', 'LIKE', "%{$search}%")
                  ->orWhere('website', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        // Sorting
        switch ($sortBy) {
            case 'score_asc':
                $query->orderBy('mobile_pagespeed', 'asc');
                break;
            case 'score_desc':
                $query->orderBy('mobile_pagespeed', 'desc');
                break;
            case 'oldest':
                $query->orderBy('id', 'asc');
                break;
            default: // latest
                $query->orderBy('id', 'desc');
                break;
        }

        $audits = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $audits,
        ]);
    }

    /**
     * Retry Single or Selected Businesses (Instant Bulk Update < 10ms)
     */
    public function retry(Request $request)
    {
        $validated = $request->validate([
            'business_ids' => 'required|array|min:1',
            'business_ids.*' => 'integer|exists:businesses,id',
        ]);

        $businessIds = $validated['business_ids'];

        // 1. Instant Bulk Update existing audit rows to 'pending'
        BusinessAudit::whereIn('business_id', $businessIds)->update([
            'psi_status' => 'pending',
            'psi_error_reason' => null,
        ]);

        // 2. Create audit records for any business missing audit row
        $existingBizIds = BusinessAudit::whereIn('business_id', $businessIds)->pluck('business_id')->toArray();
        $missingBizIds = array_diff($businessIds, $existingBizIds);

        if (!empty($missingBizIds)) {
            $insertRows = [];
            $now = now();
            foreach ($missingBizIds as $bizId) {
                $insertRows[] = [
                    'business_id' => $bizId,
                    'psi_status' => 'pending',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            BusinessAudit::insert($insertRows);
        }

        return response()->json([
            'success' => true,
            'message' => "Queued " . count($businessIds) . " website(s) for background PSI retry.",
            'data' => ['queued' => count($businessIds)],
        ]);
    }

    /**
     * Retry All Websites Matching a Missing Condition (High-Performance Bulk Update)
     */
    public function retryBatch(Request $request)
    {
        $condition = $request->input('condition', 'missing_both');
        $query = BusinessAudit::query();

        switch ($condition) {
            case 'score_only': // Missing screenshots
                $query->where(function ($q) {
                    $q->whereNotNull('mobile_pagespeed')->orWhereNotNull('desktop_pagespeed');
                })->where(function ($q) {
                    $q->whereNull('mobile_screenshot_path')->orWhere('mobile_screenshot_path', '');
                });
                break;

            case 'failed':
                $query->where('psi_status', 'failed')->orWhereNotNull('psi_error_reason');
                break;

            case 'missing_both':
            default:
                $query->whereNull('mobile_pagespeed')
                    ->whereNull('desktop_pagespeed')
                    ->where(function ($q) {
                        $q->whereNull('mobile_screenshot_path')->orWhere('mobile_screenshot_path', '');
                    });
                break;
        }

        // Fast Single Bulk Update in Database (< 50ms)
        $updatedCount = $query->update([
            'psi_status' => 'pending',
            'psi_error_reason' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Successfully queued {$updatedCount} website(s) for PSI re-audit! Status updated to 'Pending' for background processing.",
            'data' => ['queued' => $updatedCount],
        ]);
    }
}
