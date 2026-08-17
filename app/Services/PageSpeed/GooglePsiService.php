<?php

namespace App\Services\PageSpeed;

use App\Models\Business;
use App\Models\BusinessAudit;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GooglePsiService
{
    /**
     * Fetch Mobile PageSpeed Insights data and screenshot for a business website.
     *
     * @param Business $business
     * @return BusinessAudit
     * @throws \Exception
     */
    public function analyzeBusiness(Business $business): BusinessAudit
    {
        $website = trim($business->website ?? '');
        if (empty($website)) {
            throw new \InvalidArgumentException("Business #{$business->id} does not have a website URL.");
        }

        if (!preg_match('~^https?://~i', $website)) {
            $website = 'https://' . $website;
        }

        $apiKeysConfig = config('services.google.pagespeed_api_keys')
            ?: config('services.google.pagespeed_api_key')
            ?: env('PAGESPEED_API_KEYS')
            ?: env('PAGESPEED_API_KEY');
        $apiKeys = array_values(array_filter(array_map('trim', explode(',', $apiKeysConfig ?? ''))));
        $apiKey = !empty($apiKeys) ? $apiKeys[array_rand($apiKeys)] : null;

        $queryParams = [
            'url' => $website,
            'strategy' => 'mobile',
            'category' => 'performance',
        ];

        if (!empty($apiKey)) {
            $queryParams['key'] = $apiKey;
        }

        $response = Http::timeout(90)
            ->retry(2, 2000)
            ->get('https://www.googleapis.com/pagespeedonline/v5/runPagespeed', $queryParams);

        if (!$response->successful()) {
            $err = $response->json('error.message') ?? $response->body() ?? 'Failed to fetch PageSpeed Insights data';
            Log::warning("Google PSI audit skipped for Business #{$business->id} ({$website}): {$err}");

            return BusinessAudit::updateOrCreate(
                ['business_id' => $business->id],
                [
                    'psi_status' => 'failed',
                    'psi_error_reason' => mb_substr($err, 0, 255),
                    'psi_fetched_at' => now(),
                ]
            );
        }

        $data = $response->json();
        $lighthouse = $data['lighthouseResult'] ?? [];
        $audits = $lighthouse['audits'] ?? [];
        $categories = $lighthouse['categories'] ?? [];

        // Extract Metrics
        $scoreDecimal = $categories['performance']['score'] ?? null;
        $mobilePageSpeed = $scoreDecimal !== null ? (int) round($scoreDecimal * 100) : null;

        $fcp = $audits['first-contentful-paint']['displayValue'] ?? null;
        $lcp = $audits['largest-contentful-paint']['displayValue'] ?? null;
        $tbt = $audits['total-blocking-time']['displayValue'] ?? null;
        $cls = $audits['cumulative-layout-shift']['displayValue'] ?? null;
        $speedIndex = $audits['speed-index']['displayValue'] ?? null;

        // Process Screenshot
        $screenshotPath = null;
        $screenshotDataUri = $audits['final-screenshot']['details']['data'] ?? null;

        if (!empty($screenshotDataUri)) {
            $screenshotPath = $this->saveScreenshot($business->id, $screenshotDataUri);
        }

        // Update or Create BusinessAudit Record
        $audit = BusinessAudit::updateOrCreate(
            ['business_id' => $business->id],
            [
                'mobile_pagespeed' => (string) $mobilePageSpeed,
                'mobile_fcp' => $fcp,
                'mobile_lcp' => $lcp,
                'mobile_tbt' => $tbt,
                'mobile_cls' => $cls,
                'mobile_speed_index' => $speedIndex,
                'mobile_screenshot_path' => $screenshotPath,
                'psi_status' => 'completed',
                'psi_error_reason' => null,
                'psi_fetched_at' => now(),
            ]
        );

        // Trigger Auto-Sync for matching running campaigns
        try {
            app(\App\Services\Email\Campaign\CampaignAutoSyncService::class)->syncMatchingLeads($business->fresh(['audit']));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Auto-sync error for Business #{$business->id}: " . $e->getMessage());
        }

        return $audit;
    }

    /**
     * Decode base64 data URI and store in date-nested storage directory:
     * psi_screenshots/YYYY/MM/DD/business_{id}.jpg
     *
     * @param int $businessId
     * @param string $dataUri
     * @return string Relative storage path
     */
    protected function saveScreenshot(int $businessId, string $dataUri): string
    {
        $base64Data = $dataUri;
        if (str_contains($dataUri, ',')) {
            @list(, $base64Data) = explode(',', $dataUri);
        }

        $imageBinary = base64_decode($base64Data);
        if (empty($imageBinary)) {
            throw new \RuntimeException("Failed to decode base64 screenshot for business #{$businessId}");
        }

        $year = date('Y');
        $month = date('m');
        $day = date('d');

        $relativeDir = "psi_screenshots/{$year}/{$month}/{$day}";
        $filename = "business_{$businessId}.jpg";
        $relativePath = "{$relativeDir}/{$filename}";

        Storage::disk('public')->put($relativePath, $imageBinary);

        return $relativePath;
    }
}
