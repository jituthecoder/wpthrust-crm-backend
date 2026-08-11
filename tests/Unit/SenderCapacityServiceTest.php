<?php

namespace Tests\Unit;

use App\Models\Business;
use App\Models\CampaignLead;
use App\Models\CampaignSender;
use App\Models\EmailCampaign;
use App\Models\EmailSender;
use App\Models\EmailTemplate;
use App\Models\Organization;
use App\Models\User;
use App\Services\Email\Campaign\EmailSenderSelectorService;
use App\Services\Email\Campaign\SenderCapacityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SenderCapacityServiceTest extends TestCase
{
    use RefreshDatabase;

    private SenderCapacityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SenderCapacityService();
    }

    /**
     * Test 1: Daily limit 100/day baseline interval (864s)
     */
    public function test_baseline_interval_100_per_day(): void
    {
        $sender = new EmailSender(['daily_limit' => 100, 'hourly_limit' => null]);
        $this->assertEquals(864.0, $this->service->getBaselineIntervalSeconds($sender));
    }

    /**
     * Test 2: Daily limit 300/day baseline interval (288s)
     */
    public function test_baseline_interval_300_per_day(): void
    {
        $sender = new EmailSender(['daily_limit' => 300, 'hourly_limit' => null]);
        $this->assertEquals(288.0, $this->service->getBaselineIntervalSeconds($sender));
    }

    /**
     * Test 3: Daily limit 50/day baseline interval (1728s)
     */
    public function test_baseline_interval_50_per_day(): void
    {
        $sender = new EmailSender(['daily_limit' => 50, 'hourly_limit' => null]);
        $this->assertEquals(1728.0, $this->service->getBaselineIntervalSeconds($sender));
    }

    /**
     * Test 4 & 5 & 6: Hourly limit, daily limit, stricter constraint wins
     */
    public function test_stricter_limit_wins(): void
    {
        // Daily: 500/day -> 172.8s. Hourly: 10/hour -> 360s. Hourly is stricter (360s > 172.8s)
        $sender = new EmailSender(['daily_limit' => 500, 'hourly_limit' => 10]);
        $this->assertEquals(360.0, $this->service->getBaselineIntervalSeconds($sender));
    }

    /**
     * Test 7: Multiple campaigns sharing the same sender share global capacity
     */
    public function test_shared_sender_capacity_across_campaigns(): void
    {
        $organization = Organization::create(['name' => 'Org Shared', 'slug' => 'org-shared']);
        $user = User::factory()->create(['organization_id' => $organization->id]);

        $sender = EmailSender::create([
            'name' => 'Shared Sender',
            'display_name' => 'Shared Sender',
            'email' => 'shared@example.com',
            'provider' => 'smtp',
            'daily_limit' => 2,
            'hourly_limit' => 2,
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'is_active' => true,
        ]);

        // Campaign 1 reserves capacity 1
        $this->assertTrue($this->service->reserveCapacity($sender));
        // Campaign 2 reserves capacity 2 from SAME sender
        $this->assertTrue($this->service->reserveCapacity($sender));

        // Campaign 3 attempts to reserve capacity 3 from SAME sender -> rejected
        $this->assertFalse($this->service->canReserve($sender));
        $this->assertFalse($this->service->reserveCapacity($sender));
    }

    /**
     * Test 8: Sender with no remaining hourly capacity returns future availability time
     */
    public function test_hourly_limit_exhausted_returns_future_availability(): void
    {
        $organization = Organization::create(['name' => 'Org Hourly', 'slug' => 'org-hourly']);
        $user = User::factory()->create(['organization_id' => $organization->id]);

        $sender = EmailSender::create([
            'name' => 'Hourly Sender',
            'display_name' => 'Hourly Sender',
            'email' => 'hourly@example.com',
            'provider' => 'smtp',
            'daily_limit' => 100,
            'hourly_limit' => 1,
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'is_active' => true,
        ]);

        $this->service->reserveCapacity($sender);

        $nextAvailable = $this->service->getNextAvailableAt($sender);
        $this->assertTrue($nextAvailable->greaterThan(now()));
        $this->assertTrue($nextAvailable->greaterThanOrEqualTo(now()->addHour()->startOfHour()));
    }

    /**
     * Test 9: Sender with no remaining daily capacity returns future availability time
     */
    public function test_daily_limit_exhausted_returns_future_availability(): void
    {
        $organization = Organization::create(['name' => 'Org Daily', 'slug' => 'org-daily']);
        $user = User::factory()->create(['organization_id' => $organization->id]);

        $sender = EmailSender::create([
            'name' => 'Daily Sender',
            'display_name' => 'Daily Sender',
            'email' => 'daily@example.com',
            'provider' => 'smtp',
            'daily_limit' => 1,
            'hourly_limit' => 10,
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'is_active' => true,
        ]);

        $this->service->reserveCapacity($sender);

        $nextAvailable = $this->service->getNextAvailableAt($sender);
        $this->assertTrue($nextAvailable->greaterThan(now()));
        $this->assertTrue($nextAvailable->greaterThanOrEqualTo(now()->addDay()->startOfDay()));
    }

    /**
     * Test 10: Atomic/concurrency safety during capacity reservation
     */
    public function test_concurrent_reservations_are_safe(): void
    {
        $organization = Organization::create(['name' => 'Org Atomic', 'slug' => 'org-atomic']);
        $user = User::factory()->create(['organization_id' => $organization->id]);

        $sender = EmailSender::create([
            'name' => 'Atomic Sender',
            'display_name' => 'Atomic Sender',
            'email' => 'atomic@example.com',
            'provider' => 'smtp',
            'daily_limit' => 10,
            'hourly_limit' => 5,
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'is_active' => true,
        ]);

        $successCount = 0;
        for ($i = 0; $i < 7; $i++) {
            if ($this->service->reserveCapacity($sender)) {
                $successCount++;
            }
        }

        $this->assertEquals(5, $successCount);
        $sender->refresh();
        $this->assertEquals(5, $sender->reserved_this_hour);
    }

    /**
     * Test 11: Counter reset logic across boundary windows
     */
    public function test_window_resets_expire_stale_counters(): void
    {
        $organization = Organization::create(['name' => 'Org Window', 'slug' => 'org-window']);
        $user = User::factory()->create(['organization_id' => $organization->id]);

        $sender = EmailSender::create([
            'name' => 'Window Sender',
            'display_name' => 'Window Sender',
            'email' => 'window@example.com',
            'provider' => 'smtp',
            'daily_limit' => 5,
            'hourly_limit' => 5,
            'reserved_today' => 5,
            'reserved_this_hour' => 5,
            'last_daily_reset_at' => now()->subDays(2),
            'last_hourly_reset_at' => now()->subHours(2),
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'is_active' => true,
        ]);

        $this->assertTrue($this->service->canReserve($sender));
        $this->assertTrue($this->service->reserveCapacity($sender));

        $sender->refresh();
        $this->assertEquals(1, $sender->reserved_today);
        $this->assertEquals(1, $sender->reserved_this_hour);
    }

    /**
     * Test 12: Failed send/reservation reconciliation (releaseCapacity)
     */
    public function test_release_capacity_reconciles_reservation(): void
    {
        $organization = Organization::create(['name' => 'Org Release', 'slug' => 'org-release']);
        $user = User::factory()->create(['organization_id' => $organization->id]);

        $sender = EmailSender::create([
            'name' => 'Release Sender',
            'display_name' => 'Release Sender',
            'email' => 'release@example.com',
            'provider' => 'smtp',
            'daily_limit' => 1,
            'hourly_limit' => 1,
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'is_active' => true,
        ]);

        $this->assertTrue($this->service->reserveCapacity($sender));
        $this->assertFalse($this->service->canReserve($sender));

        // Provider send failed -> release capacity
        $this->service->releaseCapacity($sender);

        $this->assertTrue($this->service->canReserve($sender));
        $this->assertTrue($this->service->reserveCapacity($sender));
    }

    /**
     * Test 13: Zero division safety on null or zero limits
     */
    public function test_zero_division_safety(): void
    {
        $senderNull = new EmailSender(['daily_limit' => null, 'hourly_limit' => null]);
        $senderZero = new EmailSender(['daily_limit' => 0, 'hourly_limit' => 0]);

        $this->assertEquals(0.0, $this->service->getBaselineIntervalSeconds($senderNull));
        $this->assertEquals(0.0, $this->service->getBaselineIntervalSeconds($senderZero));
    }

    /**
     * Test 14: Inactive sender cannot reserve capacity
     */
    public function test_inactive_sender_cannot_reserve(): void
    {
        $organization = Organization::create(['name' => 'Org Inactive', 'slug' => 'org-inactive']);
        $user = User::factory()->create(['organization_id' => $organization->id]);

        $sender = EmailSender::create([
            'name' => 'Inactive Sender',
            'display_name' => 'Inactive Sender',
            'email' => 'inactive@example.com',
            'provider' => 'smtp',
            'daily_limit' => 100,
            'hourly_limit' => 10,
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'is_active' => false,
        ]);

        $this->assertFalse($this->service->canReserve($sender));
        $this->assertFalse($this->service->reserveCapacity($sender));
    }

    /**
     * Test 15: Sender selector integrates with SenderCapacityService
     */
    public function test_sender_selector_consumes_capacity_service(): void
    {
        $organization = Organization::create(['name' => 'Org Selector', 'slug' => 'org-selector']);
        $user = User::factory()->create(['organization_id' => $organization->id]);

        $sender = EmailSender::create([
            'name' => 'Selector Sender',
            'display_name' => 'Selector Sender',
            'email' => 'selector@example.com',
            'provider' => 'smtp',
            'daily_limit' => 1,
            'hourly_limit' => 1,
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'is_active' => true,
        ]);

        $template = EmailTemplate::create([
            'name' => 'T1',
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $campaign = EmailCampaign::create([
            'name' => 'C1',
            'email_template_id' => $template->id,
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $campaignSender = CampaignSender::create([
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

        $lead = CampaignLead::create([
            'email_campaign_id' => $campaign->id,
            'business_id' => $business->id,
            'status' => 'pending',
        ]);

        $selector = new EmailSenderSelectorService();
        $selected = $selector->select($lead);
        $this->assertNotNull($selected);
        $this->assertEquals($campaignSender->id, $selected->id);

        // Exhaust capacity
        $this->service->reserveCapacity($sender);

        $selectedAfterExhaustion = $selector->select($lead);
        $this->assertNull($selectedAfterExhaustion);
    }

    /**
     * Test 16: Race prevention on last available capacity (Worker A vs Worker B)
     */
    public function test_last_available_capacity_race_prevention(): void
    {
        $organization = Organization::create(['name' => 'Org Race', 'slug' => 'org-race']);
        $user = User::factory()->create(['organization_id' => $organization->id]);

        $sender = EmailSender::create([
            'name' => 'Race Sender',
            'display_name' => 'Race Sender',
            'email' => 'race@example.com',
            'provider' => 'smtp',
            'daily_limit' => 100,
            'hourly_limit' => 20,
            'sent_this_hour' => 19,
            'reserved_this_hour' => 19,
            'last_daily_reset_at' => now(),
            'last_hourly_reset_at' => now(),
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'is_active' => true,
        ]);

        // Worker A attempts reservation -> succeeds (19 -> 20)
        $workerA = $this->service->reserveCapacity($sender);
        $this->assertTrue($workerA);

        // Worker B attempts reservation immediately after -> fails (20 >= 20)
        $workerB = $this->service->reserveCapacity($sender);
        $this->assertFalse($workerB);
    }
}
