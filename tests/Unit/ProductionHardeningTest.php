<?php

namespace Tests\Unit;

use App\Jobs\SendCampaignLeadJob;
use App\Models\Business;
use App\Models\CampaignDeliveryAttempt;
use App\Models\CampaignLead;
use App\Models\CampaignSender;
use App\Models\EmailCampaign;
use App\Models\EmailSender;
use App\Models\EmailSenderAccount;
use App\Models\EmailTemplate;
use App\Models\EmailTemplateVersion;
use App\Models\Organization;
use App\Models\User;
use App\Services\Email\Campaign\CampaignDeliverySchedulerService;
use App\Services\Email\Campaign\CampaignStarterService;
use App\Services\Email\EmailCampaignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProductionHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    private function createSetup(string $campaignStatus = 'running'): array
    {
        $organization = Organization::create(['name' => 'Org Hardened', 'slug' => 'org-hardened']);
        $user = User::factory()->create(['organization_id' => $organization->id]);

        $sender = EmailSender::create([
            'name' => 'Hardened Sender',
            'display_name' => 'Hardened Sender',
            'email' => 'hardened@example.com',
            'provider' => 'gmail',
            'daily_limit' => 100,
            'hourly_limit' => 20,
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'is_active' => true,
            'reserved_today' => 0,
            'reserved_this_hour' => 0,
            'last_daily_reset_at' => now(),
            'last_hourly_reset_at' => now(),
        ]);

        EmailSenderAccount::create([
            'email_sender_id' => $sender->id,
            'settings' => ['password' => 'secret_password_123', 'mock_success' => true],
        ]);

        $template = EmailTemplate::create([
            'name' => 'T Hardened',
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $version = EmailTemplateVersion::create([
            'email_template_id' => $template->id,
            'version' => '1.0',
            'subject' => 'Subject {{business_name}}',
            'html' => '<p>HTML {{business_name}}</p>',
            'plain_text' => 'Text {{business_name}}',
            'is_current' => true,
            'created_by' => $user->id,
        ]);

        $template->update(['current_version_id' => $version->id]);

        $campaign = EmailCampaign::create([
            'name' => 'C Hardened',
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
            'company_name' => 'Hardened Co',
            'business_name' => 'Hardened Co',
            'email' => 'hardenedlead@example.com',
            'organization_id' => $organization->id,
            'assigned_user_id' => $user->id,
        ]);

        return compact('organization', 'user', 'sender', 'template', 'campaign', 'business');
    }

    /**
     * Test 1: Tenant cannot access other organization campaign via API
     */
    public function test_tenant_cannot_access_other_organization_campaign(): void
    {
        $setup = $this->createSetup('running');

        $org2 = Organization::create(['name' => 'Org 2', 'slug' => 'org-2']);
        $user2 = User::factory()->create(['organization_id' => $org2->id]);

        $response = $this->actingAs($user2, 'sanctum')
            ->getJson("/api/email-campaigns/{$setup['campaign']->id}");

        $response->assertStatus(403);
    }

    /**
     * Test 2: Campaign cancellation updates status to cancelled and preserves attempt records
     */
    public function test_campaign_cancellation_lifecycle(): void
    {
        $setup = $this->createSetup('running');
        $lead = CampaignLead::create([
            'email_campaign_id' => $setup['campaign']->id,
            'business_id' => $setup['business']->id,
            'email_sender_id' => $setup['sender']->id,
            'status' => 'pending',
            'scheduled_at' => now()->subMinute(),
        ]);

        $key = CampaignDeliveryAttempt::generateIdempotencyKey($setup['organization']->id, $setup['campaign']->id, $lead->id, 1);
        CampaignDeliveryAttempt::create([
            'organization_id' => $setup['organization']->id,
            'email_campaign_id' => $setup['campaign']->id,
            'campaign_lead_id' => $lead->id,
            'email_sender_id' => $setup['sender']->id,
            'attempt_number' => 1,
            'idempotency_key' => $key,
            'status' => 'failed',
            'failure_reason' => 'Previous failure',
        ]);

        $service = app(EmailCampaignService::class);
        $cancelledCampaign = $service->cancel($setup['campaign']);

        $this->assertEquals('cancelled', $cancelledCampaign->status);

        // Scheduler should not dispatch leads from cancelled campaign
        $scheduler = app(CampaignDeliverySchedulerService::class);
        $dispatched = $scheduler->processDueLeads();
        $this->assertEquals(0, $dispatched);

        // Attempts are preserved
        $attemptsCount = CampaignDeliveryAttempt::where('campaign_lead_id', $lead->id)->count();
        $this->assertEquals(1, $attemptsCount);
    }

    /**
     * Test 3: Cancelled and completed campaigns reject start or resume transitions
     */
    public function test_campaign_state_machine_invalid_transitions(): void
    {
        $setup = $this->createSetup('cancelled');

        $starter = app(CampaignStarterService::class);

        $this->expectException(\Exception::class);
        $starter->resume($setup['campaign']);
    }

    /**
     * Test 4: Scheduler respects per-run batch size limit
     */
    public function test_scheduler_respects_batch_size_limit(): void
    {
        $setup = $this->createSetup('running');

        for ($i = 0; $i < 5; $i++) {
            $b = Business::create([
                'company_name' => "Biz {$i}",
                'business_name' => "Biz {$i}",
                'email' => "biz{$i}@example.com",
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

        $scheduler = app(CampaignDeliverySchedulerService::class);
        $dispatched = $scheduler->processDueLeads(2, 3); // chunk = 2, maxBatchLimit = 3

        $this->assertEquals(3, $dispatched);
    }

    /**
     * Test 5: Retry all failed leads resets status to pending without upfront pre-dispatching
     */
    public function test_retry_all_failed_leads_resets_to_pending(): void
    {
        $setup = $this->createSetup('running');
        $lead = CampaignLead::create([
            'email_campaign_id' => $setup['campaign']->id,
            'business_id' => $setup['business']->id,
            'status' => 'failed',
            'retry_count' => 0,
            'max_retry' => 3,
            'failure_reason' => 'Temporary error',
        ]);

        $service = app(EmailCampaignService::class);
        $result = $service->retryAllFailedLeads($setup['campaign']);

        $this->assertEquals(1, $result['queued']);

        $lead->refresh();
        $this->assertEquals('pending', $lead->status);
        $this->assertNull($lead->failure_reason);
        $this->assertNotNull($lead->scheduled_at);

        // SendCampaignLeadJob should NOT have been pre-dispatched
        Queue::assertNothingPushed();
    }

    /**
     * Test 6: Sensitive settings are hidden from EmailSenderAccount serialization
     */
    public function test_sensitive_settings_hidden_from_json(): void
    {
        $setup = $this->createSetup('running');
        $account = EmailSenderAccount::where('email_sender_id', $setup['sender']->id)->first();

        $json = $account->toArray();
        $this->assertArrayNotHasKey('settings', $json);
    }
}
