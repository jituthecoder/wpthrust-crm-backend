<?php

namespace Tests\Unit;

use App\Models\Business;
use App\Models\CampaignLead;
use App\Models\EmailCampaign;
use App\Models\EmailTemplate;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CampaignLeadStateTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;
    protected User $user;
    protected EmailTemplate $template;
    protected EmailCampaign $campaign;
    protected Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::create([
            'name' => 'Test Org',
            'slug' => 'test-org',
            'is_active' => true,
        ]);

        $this->user = User::create([
            'organization_id' => $this->organization->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
        ]);

        $this->template = EmailTemplate::create([
            'organization_id' => $this->organization->id,
            'name' => 'Test Template',
            'template_type' => 'cold_email',
            'status' => 'published',
            'created_by' => $this->user->id,
        ]);

        $this->campaign = EmailCampaign::create([
            'organization_id' => $this->organization->id,
            'name' => 'Test Campaign',
            'email_template_id' => $this->template->id,
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $this->business = Business::create([
            'organization_id' => $this->organization->id,
            'business_name' => 'Test Business',
            'email' => 'business@example.com',
        ]);
    }

    #[Test]
    public function new_campaign_lead_starts_as_pending()
    {
        $lead = CampaignLead::create([
            'email_campaign_id' => $this->campaign->id,
            'business_id' => $this->business->id,
        ]);

        $this->assertTrue($lead->isPending());
        $this->assertEquals('pending', $lead->status);
        $this->assertEquals(0, $lead->retry_count);
        $this->assertEquals(3, $lead->fresh()->max_retry);
    }

    #[Test]
    public function scheduled_at_represents_future_delivery_time()
    {
        $future = now()->addHours(2);

        $lead = CampaignLead::create([
            'email_campaign_id' => $this->campaign->id,
            'business_id' => $this->business->id,
            'status' => 'pending',
            'scheduled_at' => $future,
        ]);

        $this->assertTrue($lead->scheduled_at->isFuture());
        $this->assertEquals(0, CampaignLead::due()->count());

        $this->travel(3)->hours();
        $this->assertEquals(1, CampaignLead::due()->count());
    }

    #[Test]
    public function state_transition_pending_to_processing_to_sent()
    {
        $lead = CampaignLead::create([
            'email_campaign_id' => $this->campaign->id,
            'business_id' => $this->business->id,
            'status' => 'pending',
        ]);

        // Move to processing
        $lead->update([
            'status' => 'processing',
            'processing_started_at' => now(),
            'last_attempt_at' => now(),
        ]);

        $this->assertTrue($lead->fresh()->isProcessing());

        // Move to sent
        $lead->update([
            'status' => 'sent',
            'sent_at' => now(),
            'processing_started_at' => null,
            'provider_message_id' => 'msg_12345',
        ]);

        $freshLead = $lead->fresh();
        $this->assertTrue($freshLead->isSent());
        $this->assertNotNull($freshLead->sent_at);
        $this->assertEquals('msg_12345', $freshLead->provider_message_id);
    }

    #[Test]
    public function state_transition_pending_to_processing_to_failed()
    {
        $lead = CampaignLead::create([
            'email_campaign_id' => $this->campaign->id,
            'business_id' => $this->business->id,
            'status' => 'pending',
        ]);

        // Move to processing
        $lead->update([
            'status' => 'processing',
            'processing_started_at' => now(),
        ]);

        // Move to failed
        $lead->update([
            'status' => 'failed',
            'failure_reason' => 'Connection timeout',
            'retry_count' => $lead->retry_count + 1,
        ]);

        $freshLead = $lead->fresh();
        $this->assertTrue($freshLead->isFailed());
        $this->assertEquals(1, $freshLead->retry_count);
        $this->assertTrue($freshLead->canRetry());
        $this->assertEquals('Connection timeout', $freshLead->failure_reason);
    }

    #[Test]
    public function failed_lead_can_be_reset_to_pending_for_retry()
    {
        $lead = CampaignLead::create([
            'email_campaign_id' => $this->campaign->id,
            'business_id' => $this->business->id,
            'status' => 'failed',
            'failure_reason' => 'SMTP error',
            'retry_count' => 1,
            'max_retry' => 3,
        ]);

        $this->assertTrue($lead->canRetry());

        $lead->update([
            'status' => 'pending',
            'failure_reason' => null,
            'processing_started_at' => null,
        ]);

        $freshLead = $lead->fresh();
        $this->assertTrue($freshLead->isPending());
        $this->assertNull($freshLead->failure_reason);
    }

    #[Test]
    public function stale_processing_leads_can_be_identified()
    {
        // Active processing lead (1 min ago)
        CampaignLead::create([
            'email_campaign_id' => $this->campaign->id,
            'business_id' => $this->business->id,
            'status' => 'processing',
            'processing_started_at' => now()->subMinutes(1),
        ]);

        // Create another business for second lead
        $business2 = Business::create([
            'organization_id' => $this->organization->id,
            'business_name' => 'Business 2',
            'email' => 'b2@example.com',
        ]);

        // Stale processing lead (15 mins ago)
        $staleLead = CampaignLead::create([
            'email_campaign_id' => $this->campaign->id,
            'business_id' => $business2->id,
            'status' => 'processing',
            'processing_started_at' => now()->subMinutes(15),
        ]);

        $staleLeads = CampaignLead::staleProcessing(10)->get();

        $this->assertCount(1, $staleLeads);
        $this->assertEquals($staleLead->id, $staleLeads->first()->id);
    }

    #[Test]
    public function lead_derives_organization_ownership_via_campaign()
    {
        $lead = CampaignLead::create([
            'email_campaign_id' => $this->campaign->id,
            'business_id' => $this->business->id,
            'status' => 'pending',
        ]);

        $this->assertEquals($this->organization->id, $lead->campaign->organization_id);
        $this->assertEquals($this->organization->id, $lead->business->organization_id);
    }
}
