<?php

namespace Tests\Unit;

use App\Models\Business;
use App\Models\CampaignLead;
use App\Models\CampaignSender;
use App\Models\EmailCampaign;
use App\Models\EmailSender;
use App\Models\EmailSenderAccount;
use App\Models\EmailTemplate;
use App\Models\Organization;
use App\Models\User;
use App\Services\Email\Campaign\CampaignStarterService;
use App\Services\Email\Campaign\RateDistributionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RateDistributionTest extends TestCase
{
    use RefreshDatabase;

    private RateDistributionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RateDistributionService();
    }

    /**
     * Test 1: Jitter calculation stays within defined percentage bounds (+/- 20%)
     */
    public function test_jitter_stays_within_percentage_bounds(): void
    {
        $baseTime = now()->addHour();
        $intervalSeconds = 864.0; // 14.4 mins
        $jitterPercent = 0.20;

        for ($i = 0; $i < 50; $i++) {
            $jittered = $this->service->applyJitter($baseTime, $intervalSeconds, $jitterPercent);
            $diffSeconds = abs($jittered->timestamp - $baseTime->timestamp);

            $maxExpectedDiff = ceil($intervalSeconds * $jitterPercent); // 173 seconds
            $this->assertLessThanOrEqual($maxExpectedDiff, $diffSeconds);
        }
    }

    /**
     * Test 2: Apply jitter does not return timestamp in the past
     */
    public function test_jitter_never_returns_past_timestamp(): void
    {
        $pastTime = now()->subMinutes(10);
        $jittered = $this->service->applyJitter($pastTime, 100.0, 0.20);

        $this->assertTrue($jittered->greaterThanOrEqualTo(now()->subSecond()));
    }

    /**
     * Test 3: Sequential lead schedules produce variable, non-identical offsets (jitter randomness)
     */
    public function test_rate_distribution_produces_jittered_offsets(): void
    {
        $organization = Organization::create(['name' => 'Org Jitter', 'slug' => 'org-jitter']);
        $user = User::factory()->create(['organization_id' => $organization->id]);

        $sender = EmailSender::create([
            'name' => 'Jitter Sender',
            'display_name' => 'Jitter Sender',
            'email' => 'jitter@example.com',
            'provider' => 'gmail',
            'daily_limit' => 100,
            'hourly_limit' => 20,
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
            'name' => 'C Jitter',
            'email_template_id' => $template->id,
            'status' => 'draft',
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        CampaignSender::create([
            'email_campaign_id' => $campaign->id,
            'email_sender_id' => $sender->id,
            'is_active' => true,
            'priority' => 1,
        ]);

        for ($i = 0; $i < 10; $i++) {
            $b = Business::create([
                'company_name' => "Jitter Co {$i}",
                'business_name' => "Jitter Co {$i}",
                'email' => "jitter{$i}@example.com",
                'organization_id' => $organization->id,
                'assigned_user_id' => $user->id,
            ]);

            CampaignLead::create([
                'email_campaign_id' => $campaign->id,
                'business_id' => $b->id,
                'status' => 'pending',
            ]);
        }

        $starter = new CampaignStarterService();
        $starter->start($campaign);

        $leads = CampaignLead::where('email_campaign_id', $campaign->id)
            ->orderBy('id')
            ->get();

        $diffs = [];
        for ($i = 1; $i < count($leads); $i++) {
            $prevTime = $leads[$i - 1]->scheduled_at->timestamp;
            $currTime = $leads[$i]->scheduled_at->timestamp;
            $diffs[] = $currTime - $prevTime;
        }

        // Verify that consecutive intervals are not all identical (jitter variance exists)
        $uniqueDiffs = array_unique($diffs);
        $this->assertGreaterThan(1, count($uniqueDiffs));
    }

    /**
     * Test 4: Configurable jitter percent via config('campaign.jitter_percent') and chronological order guard
     */
    public function test_jitter_percent_configurable_and_chronological_order(): void
    {
        config(['campaign.jitter_percent' => 0.15]); // 15% configurable

        $baseTime = now()->addHour();
        $intervalSeconds = 500.0;

        $jittered = $this->service->applyJitter($baseTime, $intervalSeconds);
        $diffSeconds = abs($jittered->timestamp - $baseTime->timestamp);
        $this->assertLessThanOrEqual(75, $diffSeconds); // 15% of 500 = 75s max offset

        // Verify strict non-decreasing chronological order across sequential lead schedules
        $organization = Organization::create(['name' => 'Org Chrono', 'slug' => 'org-chrono']);
        $user = User::factory()->create(['organization_id' => $organization->id]);

        $sender = EmailSender::create([
            'name' => 'Chrono Sender',
            'display_name' => 'Chrono Sender',
            'email' => 'chrono@example.com',
            'provider' => 'gmail',
            'daily_limit' => 100,
            'hourly_limit' => 20,
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'is_active' => true,
        ]);

        $template = EmailTemplate::create(['name' => 'T1', 'organization_id' => $organization->id, 'created_by' => $user->id]);
        $campaign = EmailCampaign::create(['name' => 'C Chrono', 'email_template_id' => $template->id, 'status' => 'draft', 'organization_id' => $organization->id, 'created_by' => $user->id]);
        CampaignSender::create(['email_campaign_id' => $campaign->id, 'email_sender_id' => $sender->id, 'is_active' => true, 'priority' => 1]);

        for ($i = 0; $i < 5; $i++) {
            $b = Business::create(['company_name' => "Chrono Co {$i}", 'business_name' => "Chrono Co {$i}", 'email' => "chrono{$i}@example.com", 'organization_id' => $organization->id, 'assigned_user_id' => $user->id]);
            CampaignLead::create(['email_campaign_id' => $campaign->id, 'business_id' => $b->id, 'status' => 'pending']);
        }

        $this->service->distributeCampaignLeads($campaign);
        $leads = CampaignLead::where('email_campaign_id', $campaign->id)->orderBy('id')->get();

        for ($i = 1; $i < count($leads); $i++) {
            $this->assertTrue(
                $leads[$i]->scheduled_at->greaterThanOrEqualTo($leads[$i - 1]->scheduled_at),
                "Lead {$i} scheduled_at must be >= Lead " . ($i - 1)
            );
        }
    }
}
