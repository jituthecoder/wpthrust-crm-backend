<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\CampaignLead;
use App\Models\EmailCampaign;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailTrackingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_track_open_endpoint_records_open_and_returns_1x1_gif()
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
            'name' => 'Test Tracking Campaign',
            'email_template_id' => $template->id,
            'created_by' => $user->id,
            'status' => 'sending',
            'sent_count' => 10,
        ]);

        $business = Business::create([
            'business_name' => 'Target Business',
            'email' => 'target@example.com',
            'phone' => '9876543210',
        ]);

        $lead = CampaignLead::create([
            'email_campaign_id' => $campaign->id,
            'business_id' => $business->id,
            'status' => 'sent',
        ]);

        $response = $this->get("/api/track/open/{$lead->id}");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/gif');

        $lead->refresh();
        $campaign->refresh();

        $this->assertNotNull($lead->opened_at);
        $this->assertEquals('opened', $lead->status);
        $this->assertEquals(1, $campaign->opened_count);
        $this->assertEquals(10.0, $campaign->open_rate);
    }

    public function test_track_click_endpoint_records_click_and_redirects()
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin2@example.com',
            'password' => bcrypt('password'),
        ]);

        $template = EmailTemplate::create([
            'name' => 'Sample Template',
            'created_by' => $user->id,
        ]);

        $campaign = EmailCampaign::create([
            'name' => 'Test Click Campaign',
            'email_template_id' => $template->id,
            'created_by' => $user->id,
            'status' => 'sending',
            'sent_count' => 10,
        ]);

        $business = Business::create([
            'business_name' => 'Click Business',
            'email' => 'click@example.com',
            'phone' => '9876543210',
        ]);

        $lead = CampaignLead::create([
            'email_campaign_id' => $campaign->id,
            'business_id' => $business->id,
            'status' => 'sent',
        ]);

        $targetUrl = 'https://wpthrust.in/demo';
        $response = $this->get("/api/track/click/{$lead->id}?url=" . urlencode($targetUrl));

        $response->assertRedirect($targetUrl);

        $lead->refresh();
        $campaign->refresh();

        $this->assertNotNull($lead->clicked_at);
        $this->assertNotNull($lead->opened_at);
        $this->assertEquals('clicked', $lead->status);
        $this->assertEquals(1, $campaign->clicked_count);
        $this->assertEquals(10.0, $campaign->click_rate);
    }
}
