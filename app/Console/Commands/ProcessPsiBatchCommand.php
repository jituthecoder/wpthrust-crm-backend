<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Models\BusinessAudit;
use App\Services\PageSpeed\GooglePsiService;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessPsiBatchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'psi:process 
                            {--concurrency=15 : Number of parallel Google API requests} 
                            {--limit=0 : Max number of leads to process (0 for unlimited)}
                            {--reset-missing : Reset audits with missing screenshot files back to pending}
                            {--reset-all : Reset ALL audits back to pending to re-audit everything}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'High-speed parallel processor for Google PageSpeed Insights audits (up to 100+ audits/min)';

    /**
     * Execute the console command.
     */
    public function handle(GooglePsiService $psiService)
    {
        $concurrency = (int) $this->option('concurrency');
        if ($concurrency < 1) $concurrency = 15;

        $limit = (int) $this->option('limit');
        $processedTotal = 0;

        if ($this->option('reset-all')) {
            $resetCount = BusinessAudit::query()->update(['psi_status' => 'pending']);
            $this->info("Reset ALL {$resetCount} audits back to pending.");
        } elseif ($this->option('reset-missing')) {
            $audits = BusinessAudit::whereNotNull('mobile_screenshot_path')->get();
            $resetCount = 0;
            foreach ($audits as $audit) {
                if (!Storage::disk('public')->exists($audit->mobile_screenshot_path)) {
                    $audit->update(['psi_status' => 'pending']);
                    $resetCount++;
                }
            }
            $this->info("Reset {$resetCount} audits with missing screenshot files back to pending.");
        }

        $this->info("Starting High-Speed Parallel PageSpeed Processor (Concurrency: {$concurrency})...");

        // Fetch keys pool
        $apiKeysConfig = env('PAGESPEED_API_KEYS') ?: config('services.google.pagespeed_api_key') ?: env('PAGESPEED_API_KEY');
        $apiKeys = array_values(array_filter(array_map('trim', explode(',', $apiKeysConfig ?? ''))));

        while (true) {
            // Check limit if set
            if ($limit > 0 && $processedTotal >= $limit) {
                $this->info("Reached limit of {$limit} processed audits. Exiting.");
                break;
            }

            // Fetch batch of pending audits
            $batchSize = $concurrency;
            $audits = BusinessAudit::where('psi_status', 'pending')
                ->whereHas('business', function ($q) {
                    $q->whereNotNull('website')
                      ->where('website', '!=', '')
                      ->where('website', '!=', '-');
                })
                ->with('business')
                ->limit($batchSize)
                ->get();

            if ($audits->isEmpty()) {
                $this->info("No more pending PageSpeed audits found. Completed total {$processedTotal} audits.");
                break;
            }

            // Mark batch as processing
            $auditIds = $audits->pluck('id')->toArray();
            BusinessAudit::whereIn('id', $auditIds)->update(['psi_status' => 'processing']);

            // Build Parallel Pool Requests
            $responses = Http::pool(function (Pool $pool) use ($audits, $apiKeys) {
                $requests = [];
                foreach ($audits as $index => $audit) {
                    $website = trim($audit->business->website ?? '');
                    if (!preg_match('~^https?://~i', $website)) {
                        $website = 'https://' . $website;
                    }

                    $queryParams = [
                        'url' => $website,
                        'strategy' => 'mobile',
                        'category' => 'performance',
                    ];

                    // Select API Key from Pool
                    if (!empty($apiKeys)) {
                        $keyIndex = $index % count($apiKeys);
                        $queryParams['key'] = $apiKeys[$keyIndex];
                    }

                    $requests[$audit->id] = $pool->as((string) $audit->id)
                        ->timeout(90)
                        ->retry(2, 1000)
                        ->get('https://www.googleapis.com/pagespeedonline/v5/runPagespeed', $queryParams);
                }
                return $requests;
            });

            // Process Responses
            foreach ($audits as $audit) {
                $response = $responses[(string) $audit->id] ?? null;

                if (!$response || !($response instanceof \Illuminate\Http\Client\Response) || !$response->successful()) {
                    $err = $response instanceof \Illuminate\Http\Client\Response
                        ? ($response->json('error.message') ?? $response->body() ?? 'Failed to fetch PageSpeed')
                        : 'Request failed or timed out';

                    $audit->update([
                        'psi_status' => 'failed',
                        'psi_error_reason' => mb_substr($err, 0, 255),
                        'psi_fetched_at' => now(),
                    ]);
                    continue;
                }

                try {
                    $data = $response->json();
                    $lighthouse = $data['lighthouseResult'] ?? [];
                    $auditMetrics = $lighthouse['audits'] ?? [];
                    $categories = $lighthouse['categories'] ?? [];

                    $scoreDecimal = $categories['performance']['score'] ?? null;
                    $mobilePageSpeed = $scoreDecimal !== null ? (int) round($scoreDecimal * 100) : null;

                    $fcp = $auditMetrics['first-contentful-paint']['displayValue'] ?? null;
                    $lcp = $auditMetrics['largest-contentful-paint']['displayValue'] ?? null;
                    $tbt = $auditMetrics['total-blocking-time']['displayValue'] ?? null;
                    $cls = $auditMetrics['cumulative-layout-shift']['displayValue'] ?? null;
                    $speedIndex = $auditMetrics['speed-index']['displayValue'] ?? null;

                    $screenshotPath = null;
                    $screenshotDataUri = $auditMetrics['final-screenshot']['details']['data'] ?? null;

                    if (!empty($screenshotDataUri)) {
                        $screenshotPath = $this->saveScreenshot($audit->business_id, $screenshotDataUri);
                    }

                    $audit->update([
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
                    ]);

                    // Trigger Auto-Sync for matching running campaigns
                    try {
                        app(\App\Services\Email\Campaign\CampaignAutoSyncService::class)->syncMatchingLeads($audit->business->fresh(['audit']));
                    } catch (\Throwable $e) {
                        Log::error("Auto-sync error for Business #{$audit->business_id}: " . $e->getMessage());
                    }

                } catch (\Throwable $e) {
                    $audit->update([
                        'psi_status' => 'failed',
                        'psi_error_reason' => mb_substr($e->getMessage(), 0, 255),
                        'psi_fetched_at' => now(),
                    ]);
                }
            }

            $batchCount = $audits->count();
            $processedTotal += $batchCount;
            $this->info("Processed batch of {$batchCount} PageSpeed audits. Total completed: {$processedTotal}");
        }

        $this->info("PageSpeed High-Speed Processing Finished! Total Audits: {$processedTotal}");

        return Command::SUCCESS;
    }

    protected function saveScreenshot(int $businessId, string $dataUri): string
    {
        $base64Data = $dataUri;
        if (str_contains($dataUri, ',')) {
            @list(, $base64Data) = explode(',', $dataUri);
        }

        $imageBinary = base64_decode($base64Data);
        if (empty($imageBinary)) {
            return '';
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
