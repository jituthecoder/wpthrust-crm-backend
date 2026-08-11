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
use App\Models\Organization;
use App\Models\User;
use App\Services\Email\Campaign\CampaignMailerService;
use App\Services\Email\Campaign\CampaignRecoveryService;
use App\Services\Email\Campaign\TemplateRendererService;
use App\Services\Email\EmailCampaignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class IdempotencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    private function createSetup(string $campaignStatus = 'running'): array
    {
        $organization = Organization::create(['name' => 'Org Idem', 'slug' => 'org-idem']);
        $user = User::factory()->create(['organization_id' => $organization->id]);

        $sender = EmailSender::create([
            'name' => 'Idem Sender',
            'display_name' => 'Idem Sender',
            'email' => 'idem@example.com',
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
            'settings' => ['mock_success' => true, 'message_id' => 'msg_idem_123'],
        ]);

        $template = EmailTemplate::create([
            'name' => 'T Idem',
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $version = \App\Models\EmailTemplateVersion::create([
            'email_template_id' => $template->id,
            'version' => '1.0',
            'subject' => 'Hello {{business_name}}',
            'html' => '<p>Hello {{business_name}}</p>',
            'plain_text' => 'Hello {{business_name}}',
            'is_current' => true,
            'created_by' => $user->id,
        ]);

        $template->update(['current_version_id' => $version->id]);

        $campaign = EmailCampaign::create([
            'name' => 'C Idem',
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
            'company_name' => 'Idem Co',
            'business_name' => 'Idem Co',
            'email' => 'idemlead@example.com',
            'organization_id' => $organization->id,
            'assigned_user_id' => $user->id,
        ]);

        return compact('organization', 'user', 'sender', 'template', 'campaign', 'business');
    }

    /**
     * Test 1: Idempotency key generation is deterministic and stable
     */
    public function test_idempotency_key_is_deterministic(): void
    {
        $key1 = CampaignDeliveryAttempt::generateIdempotencyKey(1, 2, 3, 1);
        $key2 = CampaignDeliveryAttempt::generateIdempotencyKey(1, 2, 3, 1);
        $key3 = CampaignDeliveryAttempt::generateIdempotencyKey(1, 2, 3, 2);

        $this->assertEquals($key1, $key2);
        $this->assertNotEquals($key1, $key3);
        $this->assertEquals(64, strlen($key1));
    }

    /**
     * Test 2 & 3: Database unique constraint prevents duplicate attempt records
     */
    public function test_unique_constraint_prevents_duplicate_attempts(): void
    {
        $setup = $this->createSetup();
        $lead = CampaignLead::create([
            'email_campaign_id' => $setup['campaign']->id,
            'business_id' => $setup['business']->id,
            'email_sender_id' => $setup['sender']->id,
            'status' => 'pending',
        ]);

        $key = CampaignDeliveryAttempt::generateIdempotencyKey($setup['organization']->id, $setup['campaign']->id, $lead->id, 1);

        CampaignDeliveryAttempt::create([
            'organization_id' => $setup['organization']->id,
            'email_campaign_id' => $setup['campaign']->id,
            'campaign_lead_id' => $lead->id,
            'email_sender_id' => $setup['sender']->id,
            'attempt_number' => 1,
            'idempotency_key' => $key,
            'status' => 'sending',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        CampaignDeliveryAttempt::create([
            'organization_id' => $setup['organization']->id,
            'email_campaign_id' => $setup['campaign']->id,
            'campaign_lead_id' => $lead->id,
            'email_sender_id' => $setup['sender']->id,
            'attempt_number' => 1,
            'idempotency_key' => $key,
            'status' => 'sending',
        ]);
    }

    /**
     * Test 4: Job execution creates DeliveryAttempt and records provider_message_id on success
     */
    public function test_job_creates_delivery_attempt_and_persists_provider_ids(): void
    {
        $setup = $this->createSetup('running');
        $lead = CampaignLead::create([
            'email_campaign_id' => $setup['campaign']->id,
            'business_id' => $setup['business']->id,
            'email_sender_id' => $setup['sender']->id,
            'status' => 'pending',
        ]);

        $job = new SendCampaignLeadJob($lead->id);
        $job->handle(
            app(TemplateRendererService::class),
            app(CampaignMailerService::class),
            app(EmailCampaignService::class)
        );

        $lead->refresh();
        $this->assertEquals('sent', $lead->status);
        $this->assertNotNull($lead->provider_message_id);

        $attempt = CampaignDeliveryAttempt::where('campaign_lead_id', $lead->id)->first();
        $this->assertNotNull($attempt);
        $this->assertEquals(1, $attempt->attempt_number);
        $this->assertEquals('sent', $attempt->status);
        $this->assertEquals($setup['organization']->id, $attempt->organization_id);
        $this->assertEquals($lead->provider_message_id, $attempt->provider_message_id);
    }

    /**
     * Test 5: Re-running job for an attempt already marked sent returns early without re-sending
     */
    public function test_job_with_sent_attempt_does_not_resend(): void
    {
        $setup = $this->createSetup('running');
        $lead = CampaignLead::create([
            'email_campaign_id' => $setup['campaign']->id,
            'business_id' => $setup['business']->id,
            'email_sender_id' => $setup['sender']->id,
            'status' => 'pending',
        ]);

        $key = CampaignDeliveryAttempt::generateIdempotencyKey($setup['organization']->id, $setup['campaign']->id, $lead->id, 1);
        CampaignDeliveryAttempt::create([
            'organization_id' => $setup['organization']->id,
            'email_campaign_id' => $setup['campaign']->id,
            'campaign_lead_id' => $lead->id,
            'email_sender_id' => $setup['sender']->id,
            'attempt_number' => 1,
            'idempotency_key' => $key,
            'status' => 'sent',
            'provider_message_id' => 'msg_existing_999',
            'completed_at' => now(),
        ]);

        $job = new SendCampaignLeadJob($lead->id);
        $job->handle(
            app(TemplateRendererService::class),
            app(CampaignMailerService::class),
            app(EmailCampaignService::class)
        );

        $lead->refresh();
        $this->assertEquals('sent', $lead->status);
        $this->assertEquals('msg_existing_999', $lead->provider_message_id);
    }

    /**
     * Test 6: Crash recovery updates interrupted attempt status from sending to unknown
     */
    public function test_crash_recovery_updates_attempt_to_unknown(): void
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

        $key = CampaignDeliveryAttempt::generateIdempotencyKey($setup['organization']->id, $setup['campaign']->id, $lead->id, 1);
        $attempt = CampaignDeliveryAttempt::create([
            'organization_id' => $setup['organization']->id,
            'email_campaign_id' => $setup['campaign']->id,
            'campaign_lead_id' => $lead->id,
            'email_sender_id' => $setup['sender']->id,
            'attempt_number' => 1,
            'idempotency_key' => $key,
            'status' => 'sending',
            'started_at' => now()->subMinutes(15),
        ]);

        $recovery = new CampaignRecoveryService();
        $recovered = $recovery->recoverStaleLeads();

        $this->assertEquals(1, $recovered);

        $attempt->refresh();
        $this->assertEquals('unknown', $attempt->status);
        $this->assertNotNull($attempt->failure_reason);

        $lead->refresh();
        $this->assertEquals('pending', $lead->status);
        $this->assertEquals(1, $lead->retry_count);
    }

    /**
     * Test 7: Job skips execution if attempt has unknown outcome
     */
    public function test_job_skips_attempt_with_unknown_status(): void
    {
        $setup = $this->createSetup('running');
        $lead = CampaignLead::create([
            'email_campaign_id' => $setup['campaign']->id,
            'business_id' => $setup['business']->id,
            'email_sender_id' => $setup['sender']->id,
            'status' => 'pending',
            'retry_count' => 0,
        ]);

        // Attempt 1 was marked unknown during crash recovery
        $key = CampaignDeliveryAttempt::generateIdempotencyKey($setup['organization']->id, $setup['campaign']->id, $lead->id, 1);
        CampaignDeliveryAttempt::create([
            'organization_id' => $setup['organization']->id,
            'email_campaign_id' => $setup['campaign']->id,
            'campaign_lead_id' => $lead->id,
            'email_sender_id' => $setup['sender']->id,
            'attempt_number' => 1,
            'idempotency_key' => $key,
            'status' => 'unknown',
        ]);

        // Running job for retry_count 0 looks for attempt 1 -> finds unknown -> skips
        $job = new SendCampaignLeadJob($lead->id);
        $job->handle(
            app(TemplateRendererService::class),
            app(CampaignMailerService::class),
            app(EmailCampaignService::class)
        );

        $lead->refresh();
        $this->assertEquals('pending', $lead->status);
    }

    /**
     * Test 8: Attempt history is preserved across multiple attempts
     */
    public function test_attempt_history_preserved_across_multiple_attempts(): void
    {
        $setup = $this->createSetup('running');
        $lead = CampaignLead::create([
            'email_campaign_id' => $setup['campaign']->id,
            'business_id' => $setup['business']->id,
            'email_sender_id' => $setup['sender']->id,
            'status' => 'pending',
            'retry_count' => 0,
        ]);

        $key1 = CampaignDeliveryAttempt::generateIdempotencyKey($setup['organization']->id, $setup['campaign']->id, $lead->id, 1);
        CampaignDeliveryAttempt::create([
            'organization_id' => $setup['organization']->id,
            'email_campaign_id' => $setup['campaign']->id,
            'campaign_lead_id' => $lead->id,
            'email_sender_id' => $setup['sender']->id,
            'attempt_number' => 1,
            'idempotency_key' => $key1,
            'status' => 'failed',
            'failure_reason' => 'Connection timeout',
        ]);

        $lead->update(['retry_count' => 1]);

        $key2 = CampaignDeliveryAttempt::generateIdempotencyKey($setup['organization']->id, $setup['campaign']->id, $lead->id, 2);
        CampaignDeliveryAttempt::create([
            'organization_id' => $setup['organization']->id,
            'email_campaign_id' => $setup['campaign']->id,
            'campaign_lead_id' => $lead->id,
            'email_sender_id' => $setup['sender']->id,
            'attempt_number' => 2,
            'idempotency_key' => $key2,
            'status' => 'sent',
            'provider_message_id' => 'msg_retry_success',
        ]);

        $attempts = CampaignDeliveryAttempt::where('campaign_lead_id', $lead->id)->orderBy('attempt_number')->get();
        $this->assertCount(2, $attempts);
        $this->assertEquals('failed', $attempts[0]->status);
        $this->assertEquals('sent', $attempts[1]->status);
    }
}
