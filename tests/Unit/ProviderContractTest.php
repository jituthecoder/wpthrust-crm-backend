<?php

namespace Tests\Unit;

use App\Models\Business;
use App\Models\CampaignLead;
use App\Models\CampaignSender;
use App\Models\EmailCampaign;
use App\Models\EmailSender;
use App\Models\EmailSenderAccount;
use App\Models\EmailTemplate;
use App\Models\EmailTemplateVersion;
use App\Models\Organization;
use App\Models\User;
use App\Services\Email\Campaign\CampaignMailerService;
use App\Services\Email\ProviderFactory;
use App\Services\Email\Providers\EmailProviderInterface;
use App\Services\Email\Providers\GmailProvider;
use App\Services\Email\Providers\OutlookProvider;
use App\Services\Email\Providers\ProviderDeliveryResult;
use App\Services\Email\Providers\ProviderSanitizer;
use App\Services\Email\Providers\SMTPProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailable;
use Tests\TestCase;

class ProviderContractTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 1: SMTP provider implements EmailProviderInterface
     */
    public function test_smtp_provider_implements_interface(): void
    {
        $provider = new SMTPProvider();
        $this->assertInstanceOf(EmailProviderInterface::class, $provider);
    }

    /**
     * Test 2: Gmail provider implements EmailProviderInterface
     */
    public function test_gmail_provider_implements_interface(): void
    {
        $provider = new GmailProvider();
        $this->assertInstanceOf(EmailProviderInterface::class, $provider);
    }

    /**
     * Test 3: Outlook provider implements EmailProviderInterface
     */
    public function test_outlook_provider_implements_interface(): void
    {
        $provider = new OutlookProvider();
        $this->assertInstanceOf(EmailProviderInterface::class, $provider);
    }

    /**
     * Test 4: ProviderFactory returns correct provider instance
     */
    public function test_provider_factory_returns_correct_provider(): void
    {
        $organization = Organization::create(['name' => 'Org A', 'slug' => 'org-a']);
        $user = User::factory()->create(['organization_id' => $organization->id]);

        $smtpSender = EmailSender::create([
            'name' => 'SMTP Sender',
            'display_name' => 'SMTP Sender',
            'email' => 'smtp@example.com',
            'provider' => 'smtp',
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $gmailSender = EmailSender::create([
            'name' => 'Gmail Sender',
            'display_name' => 'Gmail Sender',
            'email' => 'gmail@example.com',
            'provider' => 'gmail',
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $outlookSender = EmailSender::create([
            'name' => 'Outlook Sender',
            'display_name' => 'Outlook Sender',
            'email' => 'outlook@example.com',
            'provider' => 'outlook',
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $this->assertInstanceOf(SMTPProvider::class, ProviderFactory::make($smtpSender));
        $this->assertInstanceOf(GmailProvider::class, ProviderFactory::make($gmailSender));
        $this->assertInstanceOf(OutlookProvider::class, ProviderFactory::make($outlookSender));
    }

    /**
     * Test 5: Successful provider result follows standardized structure
     */
    public function test_successful_provider_result_structure(): void
    {
        $result = ProviderDeliveryResult::success(
            providerMessageId: 'msg_123',
            providerThreadId: 'thread_456',
            providerResponse: ['status' => 'ok']
        );

        $this->assertTrue($result->isSuccess());
        $this->assertFalse($result->isFailure());
        $this->assertEquals('msg_123', $result->providerMessageId);
        $this->assertEquals('thread_456', $result->providerThreadId);
        $this->assertEquals(['status' => 'ok'], $result->providerResponse);
        $this->assertNull($result->errorMessage);

        $array = $result->toArray();
        $this->assertTrue($array['success']);
        $this->assertEquals('msg_123', $array['provider_message_id']);
        $this->assertEquals('thread_456', $array['provider_thread_id']);
    }

    /**
     * Test 6: Provider failures are handled consistently
     */
    public function test_failed_provider_result_structure(): void
    {
        $result = ProviderDeliveryResult::failure(
            errorMessage: 'Connection timeout'
        );

        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->isFailure());
        $this->assertEquals('Connection timeout', $result->errorMessage);
        $this->assertNull($result->providerMessageId);
        $this->assertNull($result->providerThreadId);
    }

    /**
     * Test 7 & 8: Gmail message ID and thread ID are captured when available
     */
    public function test_gmail_captures_message_id_and_thread_id(): void
    {
        $provider = new GmailProvider();
        $mailable = new \App\Mail\TestEmail('Test Content');

        $result = $provider->send([
            'mock_success' => true,
            'message_id' => 'gmail_msg_999',
            'thread_id' => 'gmail_thread_888',
        ], $mailable);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('gmail_msg_999', $result->providerMessageId);
        $this->assertEquals('gmail_thread_888', $result->providerThreadId);
    }

    /**
     * Test 9: Outlook message ID and conversation/thread ID are captured when available
     */
    public function test_outlook_captures_message_id_and_thread_id(): void
    {
        $provider = new OutlookProvider();
        $mailable = new \App\Mail\TestEmail('Test Content');

        $result = $provider->send([
            'mock_success' => true,
            'id' => 'outlook_msg_777',
            'conversationId' => 'outlook_conv_666',
        ], $mailable);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('outlook_msg_777', $result->providerMessageId);
        $this->assertEquals('outlook_conv_666', $result->providerThreadId);
    }

    /**
     * Test 10: SMTP message ID captured when reliably available
     */
    public function test_smtp_returns_result_structure(): void
    {
        $provider = new SMTPProvider();
        $mailable = new \App\Mail\TestEmail('Test Content');

        // Invalid SMTP settings will trigger failure result with sanitized message
        $result = $provider->send([
            'host' => 'invalid-host-xyz.local',
            'port' => 25,
            'username' => 'user',
            'password' => 'secret_pass_123',
            'encryption' => 'tls',
        ], $mailable);

        $this->assertInstanceOf(ProviderDeliveryResult::class, $result);
        $this->assertTrue($result->isFailure());
        $this->assertFalse(str_contains($result->errorMessage ?? '', 'secret_pass_123'));
    }

    /**
     * Test 11: No credentials or secrets are exposed in provider error messages
     */
    public function test_provider_sanitizer_removes_secrets(): void
    {
        $rawError = 'Failed to connect to smtp://user:super_secret_pwd@smtp.example.com:587 with Bearer eyJhbGciOi... and access_token: "secret_token_123"';
        $sanitized = ProviderSanitizer::sanitizeMessage($rawError);

        $this->assertFalse(str_contains($sanitized, 'super_secret_pwd'));
        $this->assertFalse(str_contains($sanitized, 'eyJhbGciOi...'));
        $this->assertFalse(str_contains($sanitized, 'secret_token_123'));
        $this->assertTrue(str_contains($sanitized, '***'));
    }

    /**
     * Test 12: Existing CampaignMailerService behavior remains functional
     */
    public function test_campaign_mailer_service_returns_provider_result(): void
    {
        $organization = Organization::create(['name' => 'Org Mailer', 'slug' => 'org-mailer']);
        $user = User::factory()->create(['organization_id' => $organization->id]);

        $sender = EmailSender::create([
            'name' => 'Sender Mailer',
            'display_name' => 'Sender Mailer',
            'email' => 'sender@example.com',
            'provider' => 'gmail',
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        EmailSenderAccount::create([
            'email_sender_id' => $sender->id,
            'settings' => [
                'mock_success' => true,
                'message_id' => 'msg_mailer_1',
                'thread_id' => 'thread_mailer_1',
            ],
        ]);

        $business = Business::create([
            'company_name' => 'Target Co',
            'business_name' => 'Target Co',
            'email' => 'lead@example.com',
            'organization_id' => $organization->id,
            'assigned_user_id' => $user->id,
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

        $lead = CampaignLead::create([
            'email_campaign_id' => $campaign->id,
            'business_id' => $business->id,
            'status' => 'pending',
        ]);

        $mailer = new CampaignMailerService();
        $result = $mailer->send($lead, $sender, 'Subject Test', '<p>Hello</p>', 'Hello');

        $this->assertInstanceOf(ProviderDeliveryResult::class, $result);
        $this->assertTrue($result->isSuccess());
        $this->assertEquals('msg_mailer_1', $result->providerMessageId);
        $this->assertEquals('thread_mailer_1', $result->providerThreadId);
    }
}
