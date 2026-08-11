<?php

namespace Tests\Unit;

use App\Jobs\SendCampaignLeadJob;
use App\Models\Business;
use App\Models\CampaignLead;
use App\Models\CampaignSender;
use App\Models\EmailCampaign;
use App\Models\EmailSender;
use App\Models\EmailSenderAccount;
use App\Models\EmailTemplate;
use App\Models\Organization;
use App\Models\User;
use App\Services\Email\Campaign\CampaignDeliverySchedulerService;
use App\Services\Email\Campaign\CampaignRecoveryService;
use App\Services\Email\Campaign\SenderCapacityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CampaignRecoveryTest extends TestCase
{
    use RefreshDatabase;

    private CampaignRecoveryService $recoveryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->recoveryService = new CampaignRecoveryService();
        Queue::fake();
    }

    private function createSetup(string $campaignStatus = 'running'): array
    {
        $organization = Organization::create(['name' => 'Org Rec', 'slug' => 'org-rec']);
        $user = User::factory()->create(['organization_id' => $organization->id]);

        $sender = EmailSender::create([
            'name' => 'Rec Sender',
            'display_name' => 'Rec Sender',
            'email' => 'rec@example.com',
            'provider' => 'gmail',
            'daily_limit' => 100,
            'hourly_limit' => 20,
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'is_active' => true,
            'reserved_today' => 1,
            'reserved_this_hour' => 1,
            'last_daily_reset_at' => now(),
            'last_hourly_reset_at' => now(),
        ]);

        EmailSenderAccount::create([
            'email_sender_id' => $sender->id,
            'settings' => ['mock_success' => true],
        ]);

        $template = EmailTemplate::create([
            'name' => 'T Rec',
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $campaign = EmailCampaign::create([
            'name' => 'C Rec',
            'email_template_id' => $template->id,
            'status' => $campaignStatus,
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        CampaignSender::create([
            'email_campaign_id' => $campaign->id,
            'email_sender_id' => $sender->id,
            'is_active' => true,
            'priority' => 1,
        ]);

        $business = Business::create([
            'company_name' => 'Rec Co',
            'business_name' => 'Rec Co',
            'email' => 'reclead@example.com',
            'organization_id' => $organization->id,
            'assigned_user_id' => $user->id,
        ]);

        return compact('organization', 'user', 'sender', 'template', 'campaign', 'business');
    }

    /**
     * Test 1 & 12: Fresh processing lead (processing_started_at 2 mins ago) is NOT recovered
     */
    public function test_fresh_processing_lead_is_not_recovered(): void
    {
        $setup = $this->createSetup('running');
        $lead = CampaignLead::create([
            'email_campaign_id' => $setup['campaign']->id,
            'business_id' => $setup['business']->id,
            'email_sender_id' => $setup['sender']->id,
            'status' => 'processing',
            'processing_started_at' => now()->subMinutes(2),
        ]);

        $recovered = $this->recoveryService->recoverStaleLeads();

        $this->assertEquals(0, $recovered);
        $lead->refresh();
        $this->assertEquals('processing', $lead->status);
        $this->assertNotNull($lead->processing_started_at);
    }

    /**
     * Test 2, 3, 4, 5 & 10: Stale processing lead (processing_started_at 15 mins ago) is recovered to pending
     */
    public function test_stale_processing_lead_is_recovered_and_capacity_reconciled(): void
    {
        $setup = $this->createSetup('running');
        $lead = CampaignLead::create([
            'email_campaign_id' => $setup['campaign']->id,
            'business_id' => $setup['business']->id,
            'email_sender_id' => $setup['sender']->id,
            'status' => 'processing',
            'processing_started_at' => now()->subMinutes(15),
            'retry_count' => 0,
            'max_retry' => 3,
        ]);

        $recovered = $this->recoveryService->recoverStaleLeads();

        $this->assertEquals(1, $recovered);
        $lead->refresh();
        $this->assertEquals('pending', $lead->status);
        $this->assertNull($lead->processing_started_at);
        $this->assertEquals(1, $lead->retry_count);
        $this->assertNotNull($lead->scheduled_at);

        // Sender capacity reservation reconciled
        $setup['sender']->refresh();
        $this->assertEquals(0, $setup['sender']->reserved_today);
        $this->assertEquals(0, $setup['sender']->reserved_this_hour);
    }

    /**
     * Test 6: Recovered lead is picked up by normal scheduler when scheduled_at arrives
     */
    public function test_recovered_lead_is_dispatched_by_scheduler(): void
    {
        $setup = $this->createSetup('running');
        $lead = CampaignLead::create([
            'email_campaign_id' => $setup['campaign']->id,
            'business_id' => $setup['business']->id,
            'email_sender_id' => $setup['sender']->id,
            'status' => 'processing',
            'processing_started_at' => now()->subMinutes(15),
            'retry_count' => 0,
            'max_retry' => 3,
        ]);

        $this->recoveryService->recoverStaleLeads();
        $lead->refresh();

        // Simulate scheduled time arriving
        $lead->update(['scheduled_at' => now()->subSecond()]);

        $scheduler = new CampaignDeliverySchedulerService();
        $dispatched = $scheduler->processDueLeads();

        $this->assertEquals(1, $dispatched);
        Queue::assertPushed(SendCampaignLeadJob::class);
    }

    /**
     * Test 7: Duplicate recovery prevention (concurrency safe)
     */
    public function test_duplicate_recovery_prevention(): void
    {
        $setup = $this->createSetup('running');
        $lead = CampaignLead::create([
            'email_campaign_id' => $setup['campaign']->id,
            'business_id' => $setup['business']->id,
            'email_sender_id' => $setup['sender']->id,
            'status' => 'processing',
            'processing_started_at' => now()->subMinutes(15),
            'retry_count' => 0,
            'max_retry' => 3,
        ]);

        // First recovery succeeds
        $res1 = $this->recoveryService->recoverLead($lead->id, 10);
        $this->assertTrue($res1);

        // Second recovery immediately after returns false
        $res2 = $this->recoveryService->recoverLead($lead->id, 10);
        $this->assertFalse($res2);
    }

    /**
     * Test 8 & 9: Exhausted retry lead becomes failed with abandoned reason
     */
    public function test_exhausted_retry_lead_becomes_failed(): void
    {
        $setup = $this->createSetup('running');
        $lead = CampaignLead::create([
            'email_campaign_id' => $setup['campaign']->id,
            'business_id' => $setup['business']->id,
            'email_sender_id' => $setup['sender']->id,
            'status' => 'processing',
            'processing_started_at' => now()->subMinutes(15),
            'retry_count' => 2,
            'max_retry' => 3,
        ]);

        $recovered = $this->recoveryService->recoverStaleLeads();

        $this->assertEquals(1, $recovered);
        $lead->refresh();
        $this->assertEquals('failed', $lead->status);
        $this->assertEquals(3, $lead->retry_count);
        $this->assertNull($lead->processing_started_at);
        $this->assertStringContainsString('Delivery abandoned after exceeding processing recovery attempts.', $lead->failure_reason);
    }

    /**
     * Test 11: Sender capacity counter never drops below 0
     */
    public function test_sender_capacity_counter_never_negative(): void
    {
        $setup = $this->createSetup('running');
        $setup['sender']->update(['reserved_today' => 0, 'reserved_this_hour' => 0]);

        $capacityService = new SenderCapacityService();
        $capacityService->releaseCapacity($setup['sender']);

        $setup['sender']->refresh();
        $this->assertEquals(0, $setup['sender']->reserved_today);
        $this->assertEquals(0, $setup['sender']->reserved_this_hour);
    }

    /**
     * Test 13 & 14: Recovered lead in paused or completed campaign is not dispatched
     */
    public function test_recovered_lead_in_paused_campaign_is_not_dispatched(): void
    {
        $setup = $this->createSetup('paused');
        $lead = CampaignLead::create([
            'email_campaign_id' => $setup['campaign']->id,
            'business_id' => $setup['business']->id,
            'email_sender_id' => $setup['sender']->id,
            'status' => 'processing',
            'processing_started_at' => now()->subMinutes(15),
        ]);

        $this->recoveryService->recoverStaleLeads();
        $lead->refresh();
        $lead->update(['scheduled_at' => now()->subSecond()]);

        $scheduler = new CampaignDeliverySchedulerService();
        $dispatched = $scheduler->processDueLeads();

        $this->assertEquals(0, $dispatched);
        Queue::assertNothingPushed();
    }

    /**
     * Test 15 & 16: Artisan command campaigns:recover executes recovery safely and repeatedly
     */
    public function test_artisan_command_campaigns_recover(): void
    {
        $setup = $this->createSetup('running');
        CampaignLead::create([
            'email_campaign_id' => $setup['campaign']->id,
            'business_id' => $setup['business']->id,
            'email_sender_id' => $setup['sender']->id,
            'status' => 'processing',
            'processing_started_at' => now()->subMinutes(15),
        ]);

        Artisan::call('campaigns:recover');

        $this->assertDatabaseHas('campaign_leads', [
            'email_campaign_id' => $setup['campaign']->id,
            'status' => 'pending',
        ]);

        // Run command a second time -> idempotent
        Artisan::call('campaigns:recover');
    }

    /**
     * Test 17: Shared sender across multiple campaigns is reconciled safely during recovery
     */
    public function test_shared_sender_across_campaigns_in_recovery(): void
    {
        $setup1 = $this->createSetup('running');
        $setup1['sender']->update(['reserved_today' => 2, 'reserved_this_hour' => 2]);

        // Campaign 2 sharing the same sender
        $campaign2 = EmailCampaign::create([
            'name' => 'C Shared Rec',
            'email_template_id' => $setup1['template']->id,
            'status' => 'running',
            'organization_id' => $setup1['organization']->id,
            'created_by' => $setup1['user']->id,
        ]);

        CampaignSender::create([
            'email_campaign_id' => $campaign2->id,
            'email_sender_id' => $setup1['sender']->id,
            'is_active' => true,
            'priority' => 1,
        ]);

        $b2 = Business::create([
            'company_name' => 'Rec Co 2',
            'business_name' => 'Rec Co 2',
            'email' => 'reclead2@example.com',
            'organization_id' => $setup1['organization']->id,
            'assigned_user_id' => $setup1['user']->id,
        ]);

        // Lead 1 in Campaign 1 (Stale processing)
        $lead1 = CampaignLead::create([
            'email_campaign_id' => $setup1['campaign']->id,
            'business_id' => $setup1['business']->id,
            'email_sender_id' => $setup1['sender']->id,
            'status' => 'processing',
            'processing_started_at' => now()->subMinutes(15),
        ]);

        // Lead 2 in Campaign 2 (Active processing)
        $lead2 = CampaignLead::create([
            'email_campaign_id' => $campaign2->id,
            'business_id' => $b2->id,
            'email_sender_id' => $setup1['sender']->id,
            'status' => 'processing',
            'processing_started_at' => now()->subMinutes(2),
        ]);

        $recovered = $this->recoveryService->recoverStaleLeads();

        $this->assertEquals(1, $recovered);

        $lead1->refresh();
        $lead2->refresh();

        $this->assertEquals('pending', $lead1->status);
        $this->assertEquals('processing', $lead2->status);

        // Sender capacity decremented by 1 (reconciling only Lead 1)
        $setup1['sender']->refresh();
        $this->assertEquals(1, $setup1['sender']->reserved_today);
        $this->assertEquals(1, $setup1['sender']->reserved_this_hour);
    }
}
