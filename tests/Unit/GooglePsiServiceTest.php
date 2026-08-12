<?php

namespace Tests\Unit;

use App\Models\Business;
use App\Models\BusinessAudit;
use App\Services\PageSpeed\GooglePsiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GooglePsiServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_psi_service_parses_metrics_and_saves_date_nested_screenshot()
    {
        Storage::fake('public');

        $business = Business::create([
            'business_name' => 'Test Business',
            'website' => 'https://example.com',
            'email' => 'contact@example.com',
            'phone' => '1234567890',
        ]);

        // Fake Google PSI API Response
        $fakeBase64Image = base64_encode('fake-jpeg-binary-content');
        Http::fake([
            'https://www.googleapis.com/pagespeedonline/v5/runPagespeed*' => Http::response([
                'lighthouseResult' => [
                    'categories' => [
                        'performance' => [
                            'score' => 0.85,
                        ],
                    ],
                    'audits' => [
                        'first-contentful-paint' => ['displayValue' => '1.2 s'],
                        'largest-contentful-paint' => ['displayValue' => '2.4 s'],
                        'total-blocking-time' => ['displayValue' => '150 ms'],
                        'cumulative-layout-shift' => ['displayValue' => '0.02'],
                        'speed-index' => ['displayValue' => '2.1 s'],
                        'final-screenshot' => [
                            'details' => [
                                'data' => 'data:image/jpeg;base64,' . $fakeBase64Image,
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new GooglePsiService();
        $audit = $service->analyzeBusiness($business);

        $this->assertEquals('85', $audit->mobile_pagespeed);
        $this->assertEquals('1.2 s', $audit->mobile_fcp);
        $this->assertEquals('2.4 s', $audit->mobile_lcp);
        $this->assertEquals('150 ms', $audit->mobile_tbt);
        $this->assertEquals('0.02', $audit->mobile_cls);
        $this->assertEquals('2.1 s', $audit->mobile_speed_index);
        $this->assertEquals('completed', $audit->psi_status);
        $this->assertNotNull($audit->psi_fetched_at);

        $year = date('Y');
        $month = date('m');
        $day = date('d');
        $expectedPath = "psi_screenshots/{$year}/{$month}/{$day}/business_{$business->id}.jpg";

        $this->assertEquals($expectedPath, $audit->mobile_screenshot_path);
        Storage::disk('public')->assertExists($expectedPath);
    }
}
