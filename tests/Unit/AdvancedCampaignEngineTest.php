<?php

namespace Tests\Unit;

use App\Models\Business;
use App\Models\BusinessAudit;
use App\Models\CampaignLead;
use App\Models\CampaignSequenceStep;
use App\Models\EmailCampaign;
use App\Models\EmailTemplate;
use App\Models\EmailTemplateVersion;
use App\Models\Organization;
use App\Models\UnsubscribedEmail;
use App\Models\User;
use App\Services\Email\Campaign\CampaignAutoSyncService;
use App\Services\Email\Campaign\CampaignSequenceSchedulerService;
use App\Services\Email\Tracking\EmailTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvancedCampaignEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_unsubscribe_link_generation_and_footer()
    {
        $org = Organization::create(['name' => 'Org 1', 'slug' => 'org-1']);
        $user = User::create(['name' => 'User', 'email' => 'u1@test.com', 'password' => 'secret', 'organization_id' => $org->id]);
        $template = EmailTemplate::create(['name' => 'T1', 'created_by' => $user->id, 'organization_id' => $org->id]);
        $campaign = EmailCampaign::create(['name' => 'C1', 'email_template_id' => $template->id, 'created_by' => $user->id, 'organization_id' => $org->id]);
        $biz = Business::create(['organization_id' => $org->id, 'business_name' => 'B1', 'email' => 'b1@test.com']);
        $lead = CampaignLead::create(['email_campaign_id' => $campaign->id, 'business_id' => $biz->id]);

        $trackingService = new EmailTrackingService();
        $html = $trackingService->prepareTrackedHtml('<p>Hello World</p>', $lead);

        $this->assertStringContainsString('/api/track/unsubscribe/', $html);
        $this->assertNotEmpty($lead->fresh()->unsubscribe_token);
    }

    public function test_track_unsubscribe_endpoint()
    {
        $org = Organization::create(['name' => 'Org 2', 'slug' => 'org-2']);
        $user = User::create(['name' => 'User2', 'email' => 'u2@test.com', 'password' => 'secret', 'organization_id' => $org->id]);
        $template = EmailTemplate::create(['name' => 'T2', 'created_by' => $user->id, 'organization_id' => $org->id]);
        $campaign = EmailCampaign::create(['name' => 'C2', 'email_template_id' => $template->id, 'created_by' => $user->id, 'organization_id' => $org->id]);
        $biz = Business::create(['organization_id' => $org->id, 'business_name' => 'B2', 'email' => 'unsub@test.com']);
        $lead = CampaignLead::create(['email_campaign_id' => $campaign->id, 'business_id' => $biz->id, 'unsubscribe_token' => 'token12345']);

        $response = $this->get('/api/track/unsubscribe/token12345');

        $response->assertStatus(200);
        $this->assertStringContainsString('You have been unsubscribed', $response->getContent());
        $this->assertEquals('unsubscribed', $lead->fresh()->status);
        $this->assertDatabaseHas('unsubscribed_emails', [
            'organization_id' => $org->id,
            'email' => 'unsub@test.com',
        ]);
    }

    public function test_auto_sync_matching_leads_to_active_campaign()
    {
        $org = Organization::create(['name' => 'Org 3', 'slug' => 'org-3']);
        $user = User::create(['name' => 'User3', 'email' => 'u3@test.com', 'password' => 'secret', 'organization_id' => $org->id]);
        $template = EmailTemplate::create(['name' => 'T3', 'created_by' => $user->id, 'organization_id' => $org->id]);
        
        $campaign = EmailCampaign::create([
            'name' => 'Auto Sync Campaign',
            'email_template_id' => $template->id,
            'created_by' => $user->id,
            'organization_id' => $org->id,
            'status' => 'running',
            'auto_sync_enabled' => true,
            'auto_sync_criteria' => [
                'has_website' => 'yes',
                'psi_filter' => 'less_50',
            ],
        ]);

        $biz = Business::create([
            'organization_id' => $org->id,
            'business_name' => 'Low PSI Plumbing',
            'email' => 'plumbing@test.com',
            'website' => 'https://lowpsi.com',
            'category' => 'Plumber',
        ]);

        BusinessAudit::create([
            'business_id' => $biz->id,
            'mobile_pagespeed' => '35',
            'psi_status' => 'completed',
        ]);

        $autoSyncService = new CampaignAutoSyncService();
        $synced = $autoSyncService->syncMatchingLeads(Business::with('audit')->find($biz->id));

        $this->assertEquals(1, $synced);
        $this->assertDatabaseHas('campaign_leads', [
            'email_campaign_id' => $campaign->id,
            'business_id' => $biz->id,
            'status' => 'pending',
        ]);
    }

    public function test_conditional_sequence_steps_evaluation()
    {
        $org = Organization::create(['name' => 'Org 4', 'slug' => 'org-4']);
        $user = User::create(['name' => 'User4', 'email' => 'u4@test.com', 'password' => 'secret', 'organization_id' => $org->id]);
        $t1 = EmailTemplate::create(['name' => 'Step 1 Template', 'created_by' => $user->id, 'organization_id' => $org->id]);
        $v1 = EmailTemplateVersion::create(['email_template_id' => $t1->id, 'created_by' => $user->id, 'subject' => 'S1', 'html' => 'B1', 'version' => 1, 'is_published' => true]);
        $t1->update(['current_version_id' => $v1->id]);

        $t2 = EmailTemplate::create(['name' => 'Step 2 Followup', 'created_by' => $user->id, 'organization_id' => $org->id]);
        $v2 = EmailTemplateVersion::create(['email_template_id' => $t2->id, 'created_by' => $user->id, 'subject' => 'S2', 'html' => 'B2', 'version' => 1, 'is_published' => true]);
        $t2->update(['current_version_id' => $v2->id]);

        $campaign = EmailCampaign::create([
            'name' => 'Sequence Campaign',
            'email_template_id' => $t1->id,
            'created_by' => $user->id,
            'organization_id' => $org->id,
            'status' => 'running',
        ]);

        $step1 = CampaignSequenceStep::create([
            'email_campaign_id' => $campaign->id,
            'step_number' => 1,
            'email_template_id' => $t1->id,
            'delay_days' => 0,
            'condition' => 'always',
        ]);

        $step2 = CampaignSequenceStep::create([
            'email_campaign_id' => $campaign->id,
            'step_number' => 2,
            'email_template_id' => $t2->id,
            'delay_days' => 1,
            'condition' => 'if_opened',
        ]);

        $biz = Business::create(['organization_id' => $org->id, 'business_name' => 'Seq Biz', 'email' => 'seq@test.com']);
        $lead = CampaignLead::create([
            'email_campaign_id' => $campaign->id,
            'business_id' => $biz->id,
            'status' => 'opened',
            'sent_at' => now()->subDays(2),
            'opened_at' => now()->subDays(1),
        ]);

        $schedulerService = app(CampaignSequenceSchedulerService::class);
        $processed = $schedulerService->processDueSequenceSteps();

        $this->assertDatabaseHas('campaign_lead_steps', [
            'campaign_lead_id' => $lead->id,
            'step_number' => 2,
        ]);
    }
}
