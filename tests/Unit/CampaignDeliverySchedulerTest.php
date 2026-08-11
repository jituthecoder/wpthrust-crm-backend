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
use App\Services\Email\Campaign\CampaignStarterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CampaignDeliverySchedulerTest extends TestCase
{
    use RefreshDatabase;

    private CampaignDeliverySchedulerService $scheduler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scheduler = new CampaignDeliverySchedulerService();
        Queue::fake();
    }

    private function createCampaignSetup(string $campaignStatus = 'running', int $dailyLimit = 100): array
    {
        $organization = Organization::create(['name' => 'Org Sched', 'slug' => 'org-sched']);
        $user = User::factory()->create(['organization_id' => $organization->id]);

        $sender = EmailSender::create([
            'name' => 'Sched Sender',
            'display_name' => 'Sched Sender',
            'email' => 'sched@example.com',
            'provider' => 'gmail',
            'daily_limit' => $dailyLimit,
            'hourly_limit' => 20,
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'is_active' => true,
        ]);

        EmailSenderAccount::create([
            'email_sender_id' => $sender->id,
            'settings' => ['mock_success' => true, 'message_id' => 'msg_sched_1'],
        ]);

        $template = EmailTemplate::create([
            'name' => 'T1',
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $campaign = EmailCampaign::create([
            'name' => 'C1',
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
            'company_name' => 'Lead Co',
            'business_name' => 'Lead Co',
            'email' => 'lead@example.com',
            'organization_id' => $organization->id,
            'assigned_user_id' => $user->id,
        ]);

        return compact('organization', 'user', 'sender', 'template', 'campaign', 'business');
    }

    /**
     * Test 1 & 14: Due pending lead is dispatched when scheduled_at <= now
     */
    public function test_due_pending_lead_is_dispatched(): void
    {
        $setup = $this->createCampaignSetup('running');
        $lead = CampaignLead::create([
            'email_campaign_id' => $setup['campaign']->id,
            'business_id' => $setup['business']->id,
            'status' => 'pending',
            'scheduled_at' => now()->subMinute(),
        ]);

        $dispatched = $this->scheduler->processDueLeads();

        $this->assertEquals(1, $dispatched);
        Queue::assertPushed(SendCampaignLeadJob::class, function ($job) use ($lead) {
            return true;
        });
        $lead->refresh();
        $this->assertEquals('processing', $lead->status);
    }

    /**
     * Test 2: Future scheduled lead is NOT dispatched
     */
    public function test_future_scheduled_lead_is_not_dispatched(): void
    {
        $setup = $this->createCampaignSetup('running');
        $lead = CampaignLead::create([
            'email_campaign_id' => $setup['campaign']->id,
            'business_id' => $setup['business']->id,
            'status' => 'pending',
            'scheduled_at' => now()->addHour(),
        ]);

        $dispatched = $this->scheduler->processDueLeads();

        $this->assertEquals(0, $dispatched);
        Queue::assertNothingPushed();
        $lead->refresh();
        $this->assertEquals('pending', $lead->status);
    }

    /**
     * Test 3: Sent lead is NOT dispatched
     */
    public function test_sent_lead_is_not_dispatched(): void
    {
        $setup = $this->createCampaignSetup('running');
        $lead = CampaignLead::create([
            'email_campaign_id' => $setup['campaign']->id,
            'business_id' => $setup['business']->id,
            'status' => 'sent',
            'scheduled_at' => now()->subMinute(),
        ]);

        $dispatched = $this->scheduler->processDueLeads();

        $this->assertEquals(0, $dispatched);
        Queue::assertNothingPushed();
    }

    /**
     * Test 4: Processing lead is NOT dispatched
     */
    public function test_processing_lead_is_not_dispatched(): void
    {
        $setup = $this->createCampaignSetup('running');
        $lead = CampaignLead::create([
            'email_campaign_id' => $setup['campaign']->id,
            'business_id' => $setup['business']->id,
            'status' => 'processing',
            'scheduled_at' => now()->subMinute(),
        ]);

        $dispatched = $this->scheduler->processDueLeads();

        $this->assertEquals(0, $dispatched);
        Queue::assertNothingPushed();
    }

    /**
     * Test 5: Failed lead is NOT dispatched
     */
    public function test_failed_lead_is_not_dispatched(): void
    {
        $setup = $this->createCampaignSetup('running');
        $lead = CampaignLead::create([
            'email_campaign_id' => $setup['campaign']->id,
            'business_id' => $setup['business']->id,
            'status' => 'failed',
            'scheduled_at' => now()->subMinute(),
        ]);

        $dispatched = $this->scheduler->processDueLeads();

        $this->assertEquals(0, $dispatched);
        Queue::assertNothingPushed();
    }

    /**
     * Test 6: Paused campaign does NOT dispatch leads
     */
    public function test_paused_campaign_does_not_dispatch_leads(): void
    {
        $setup = $this->createCampaignSetup('paused');
        $lead = CampaignLead::create([
            'email_campaign_id' => $setup['campaign']->id,
            'business_id' => $setup['business']->id,
            'status' => 'pending',
            'scheduled_at' => now()->subMinute(),
        ]);

        $dispatched = $this->scheduler->processDueLeads();

        $this->assertEquals(0, $dispatched);
        Queue::assertNothingPushed();
    }

    /**
     * Test 7: Completed campaign does NOT dispatch leads
     */
    public function test_completed_campaign_does_not_dispatch_leads(): void
    {
        $setup = $this->createCampaignSetup('completed');
        $lead = CampaignLead::create([
            'email_campaign_id' => $setup['campaign']->id,
            'business_id' => $setup['business']->id,
            'status' => 'pending',
            'scheduled_at' => now()->subMinute(),
        ]);

        $dispatched = $this->scheduler->processDueLeads();

        $this->assertEquals(0, $dispatched);
        Queue::assertNothingPushed();
    }

    /**
     * Test 8: Cancelled campaign does NOT dispatch leads
     */
    public function test_cancelled_campaign_does_not_dispatch_leads(): void
    {
        $setup = $this->createCampaignSetup('cancelled');
        $lead = CampaignLead::create([
            'email_campaign_id' => $setup['campaign']->id,
            'business_id' => $setup['business']->id,
            'status' => 'pending',
            'scheduled_at' => now()->subMinute(),
        ]);

        $dispatched = $this->scheduler->processDueLeads();

        $this->assertEquals(0, $dispatched);
        Queue::assertNothingPushed();
    }

    /**
     * Test 9: Scheduler processes large lead sets via chunking
     */
    public function test_scheduler_chunking_handles_multiple_leads(): void
    {
        $setup = $this->createCampaignSetup('running');
        for ($i = 0; $i < 15; $i++) {
            $b = Business::create([
                'company_name' => "Lead Co {$i}",
                'business_name' => "Lead Co {$i}",
                'email' => "lead{$i}@example.com",
                'organization_id' => $setup['organization']->id,
                'assigned_user_id' => $setup['user']->id,
            ]);

            CampaignLead::create([
                'email_campaign_id' => $setup['campaign']->id,
                'business_id' => $b->id,
                'status' => 'pending',
                'scheduled_at' => now()->subMinute(),
            ]);
        }

        $dispatched = $this->scheduler->processDueLeads(5);

        $this->assertEquals(15, $dispatched);
        Queue::assertPushed(SendCampaignLeadJob::class, 15);
    }

    /**
     * Test 10: Same lead is NOT dispatched twice if scheduler runs twice
     */
    public function test_duplicate_dispatch_protection(): void
    {
        $setup = $this->createCampaignSetup('running');
        $lead = CampaignLead::create([
            'email_campaign_id' => $setup['campaign']->id,
            'business_id' => $setup['business']->id,
            'status' => 'pending',
            'scheduled_at' => now()->subMinute(),
        ]);

        // First run dispatches
        $dispatched1 = $this->scheduler->processDueLeads();
        $this->assertEquals(1, $dispatched1);

        // Second run immediately after -> 0 dispatched
        $dispatched2 = $this->scheduler->processDueLeads();
        $this->assertEquals(0, $dispatched2);

        Queue::assertPushed(SendCampaignLeadJob::class, 1);
    }

    /**
     * Test 12: Campaign start does NOT enqueue all jobs immediately
     */
    public function test_campaign_start_does_not_enqueue_jobs_upfront(): void
    {
        $setup = $this->createCampaignSetup('draft');

        for ($i = 0; $i < 5; $i++) {
            $b = Business::create([
                'company_name' => "Lead Start Co {$i}",
                'business_name' => "Lead Start Co {$i}",
                'email' => "leadstart{$i}@example.com",
                'organization_id' => $setup['organization']->id,
                'assigned_user_id' => $setup['user']->id,
            ]);

            CampaignLead::create([
                'email_campaign_id' => $setup['campaign']->id,
                'business_id' => $b->id,
                'status' => 'pending',
            ]);
        }

        $starter = new CampaignStarterService();
        $starter->start($setup['campaign']);

        // Zero queue jobs should be pushed during campaign start
        Queue::assertNothingPushed();

        // But scheduled_at timestamps should be populated
        $pendingLeads = CampaignLead::where('email_campaign_id', $setup['campaign']->id)->get();
        foreach ($pendingLeads as $lead) {
            $this->assertNotNull($lead->scheduled_at);
        }
    }

    /**
     * Test 13: Campaign resume does NOT re-dispatch already scheduled work
     */
    public function test_campaign_resume_does_not_duplicate_jobs(): void
    {
        $setup = $this->createCampaignSetup('paused');
        CampaignLead::create([
            'email_campaign_id' => $setup['campaign']->id,
            'business_id' => $setup['business']->id,
            'status' => 'pending',
            'scheduled_at' => now()->subMinute(),
        ]);

        $starter = new CampaignStarterService();
        $starter->resume($setup['campaign']);

        Queue::assertNothingPushed();
        $this->assertEquals('running', $setup['campaign']->fresh()->status);
    }

    /**
     * Test 18 & 19: Artisan command campaigns:process invokes scheduler safely and repeatedly
     */
    public function test_command_invokes_scheduler_repeatedly(): void
    {
        $setup = $this->createCampaignSetup('running');
        CampaignLead::create([
            'email_campaign_id' => $setup['campaign']->id,
            'business_id' => $setup['business']->id,
            'status' => 'pending',
            'scheduled_at' => now()->subMinute(),
        ]);

        Artisan::call('campaigns:process');

        Queue::assertPushed(SendCampaignLeadJob::class, 1);

        // Run command a second time -> idempotent
        Artisan::call('campaigns:process');

        Queue::assertPushed(SendCampaignLeadJob::class, 1);
    }
}
