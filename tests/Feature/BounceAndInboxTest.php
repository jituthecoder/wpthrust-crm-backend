<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\CampaignLead;
use App\Models\EmailCampaign;
use App\Models\EmailSender;
use App\Models\EmailTemplate;
use App\Models\Organization;
use App\Models\User;
use App\Services\Email\Campaign\CampaignAutoSyncService;
use App\Services\Email\Inbox\BounceParserService;
use App\Services\Email\Inbox\InboxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BounceAndInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_bounce_parser_updates_business_and_campaign_lead()
    {
        $org = Organization::create(['name' => 'Test Org', 'slug' => 'test-org']);
        $user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'organization_id' => $org->id,
        ]);

        $template = EmailTemplate::create([
            'organization_id' => $org->id,
            'created_by' => $user->id,
            'name' => 'Test Template',
            'subject' => 'Hello',
            'body_html' => '<p>Hi</p>',
        ]);
        
        $business = Business::create([
            'organization_id' => $org->id,
            'business_name' => 'Dr Davis Nguyen',
            'email' => 'info@drdavisnguyen.com',
            'lead_status' => 'new',
        ]);

        $campaign = EmailCampaign::create([
            'organization_id' => $org->id,
            'email_template_id' => $template->id,
            'created_by' => $user->id,
            'name' => 'Test Campaign',
            'status' => 'running',
            'total_leads' => 1,
        ]);

        $campaignLead = CampaignLead::create([
            'email_campaign_id' => $campaign->id,
            'business_id' => $business->id,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $bounceService = app(BounceParserService::class);

        $bounceData = [
            'from_email' => 'mailer-daemon@googlemail.com',
            'from_name' => 'Mail Delivery Subsystem',
            'subject' => 'Delivery incomplete',
            'body_text' => 'There was a temporary problem delivering your message to info@drdavisnguyen.com. DNS Error: DNS type mx lookup responded with code NOERROR.',
        ];

        $this->assertTrue($bounceService->isBounceMessage($bounceData));
        $extractedEmail = $bounceService->extractBouncedEmail($bounceData);
        $this->assertEquals('info@drdavisnguyen.com', $extractedEmail);

        $result = $bounceService->processBounce($extractedEmail, 'DNS Error');

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['businesses_updated']);
        $this->assertEquals(1, $result['campaign_leads_updated']);

        // Assert Business updated
        $business->refresh();
        $this->assertTrue((bool)$business->is_bounced);
        $this->assertNotNull($business->bounced_at);
        $this->assertEquals('bounced', $business->lead_status);

        // Assert CampaignLead updated
        $campaignLead->refresh();
        $this->assertEquals('bounced', $campaignLead->status);
        $this->assertNotNull($campaignLead->bounced_at);

        // Assert Campaign stats updated
        $campaign->refresh();
        $this->assertEquals(1, $campaign->bounced_count);
    }

    public function test_bounced_leads_are_excluded_from_new_campaigns()
    {
        $org = Organization::create(['name' => 'Test Org', 'slug' => 'test-org']);
        $user = User::create([
            'name' => 'Admin User',
            'email' => 'admin2@example.com',
            'password' => bcrypt('password'),
            'organization_id' => $org->id,
        ]);

        $template = EmailTemplate::create([
            'organization_id' => $org->id,
            'created_by' => $user->id,
            'name' => 'Test Template',
            'subject' => 'Hello',
            'body_html' => '<p>Hi</p>',
        ]);
        
        $bouncedBusiness = Business::create([
            'organization_id' => $org->id,
            'business_name' => 'Bounced Lead',
            'email' => 'bounced@example.com',
            'is_bounced' => true,
            'bounced_at' => now(),
            'lead_status' => 'bounced',
        ]);

        $validBusiness = Business::create([
            'organization_id' => $org->id,
            'business_name' => 'Valid Lead',
            'email' => 'valid@example.com',
            'is_bounced' => false,
            'lead_status' => 'new',
        ]);

        $autoSyncService = app(CampaignAutoSyncService::class);

        $campaign = EmailCampaign::create([
            'organization_id' => $org->id,
            'email_template_id' => $template->id,
            'created_by' => $user->id,
            'name' => 'New AutoSync Campaign',
            'status' => 'running',
            'auto_sync_enabled' => true,
            'auto_sync_criteria' => ['exclude_bounced' => true],
        ]);

        // Auto sync all matching leads
        $attached = $autoSyncService->syncAllMatchingLeads($campaign);

        $this->assertEquals(1, $attached);
        $this->assertDatabaseHas('campaign_leads', [
            'email_campaign_id' => $campaign->id,
            'business_id' => $validBusiness->id,
        ]);
        $this->assertDatabaseMissing('campaign_leads', [
            'email_campaign_id' => $campaign->id,
            'business_id' => $bouncedBusiness->id,
        ]);
    }
}
