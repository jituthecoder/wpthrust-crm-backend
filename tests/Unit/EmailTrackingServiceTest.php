<?php

namespace Tests\Unit;

use App\Models\Business;
use App\Models\CampaignLead;
use App\Models\EmailCampaign;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Services\Email\Tracking\EmailTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailTrackingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_injects_open_tracking_pixel_and_rewrites_links()
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $template = EmailTemplate::create([
            'name' => 'Sample Template',
            'created_by' => $user->id,
        ]);

        $campaign = EmailCampaign::create([
            'name' => 'Test Campaign',
            'email_template_id' => $template->id,
            'created_by' => $user->id,
            'status' => 'draft',
        ]);

        $business = Business::create([
            'business_name' => 'Acme Corp',
            'website' => 'https://acme.com',
            'email' => 'contact@acme.com',
            'phone' => '1234567890',
        ]);

        $lead = CampaignLead::create([
            'email_campaign_id' => $campaign->id,
            'business_id' => $business->id,
            'status' => 'sent',
        ]);

        $service = new EmailTrackingService();

        $originalHtml = '<html><body><h1>Hello Acme</h1><p>Check our <a href="https://wpthrust.in/pricing">Pricing</a> page.</p></body></html>';
        $trackedHtml = $service->prepareTrackedHtml($originalHtml, $lead);

        // Check Open Pixel Injection
        $this->assertStringContainsString('/api/track/open/' . $lead->id, $trackedHtml);

        // Check Link Rewriting
        $this->assertStringContainsString('/api/track/click/' . $lead->id . '?url=', $trackedHtml);
        $this->assertStringContainsString(urlencode('https://wpthrust.in/pricing'), $trackedHtml);
    }
}
