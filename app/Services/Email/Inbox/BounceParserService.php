<?php

namespace App\Services\Email\Inbox;

use App\Models\Business;
use App\Models\CampaignLead;
use App\Models\EmailCampaign;
use App\Models\LeadActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BounceParserService
{
    /**
     * Determine if an email header/body indicates a bounce notification.
     */
    public function isBounceMessage(array $messageData): bool
    {
        $from = strtolower($messageData['from_email'] ?? '');
        $fromName = strtolower($messageData['from_name'] ?? '');
        $subject = strtolower($messageData['subject'] ?? '');

        // Bounce sender patterns
        $bounceSenders = [
            'mailer-daemon@',
            'postmaster@',
            'mail delivery subsystem',
            'system-bounce',
            'bounce',
            'donotreply@'
        ];

        foreach ($bounceSenders as $pattern) {
            if (str_contains($from, $pattern) || str_contains($fromName, $pattern)) {
                return true;
            }
        }

        // Bounce subject keywords
        $bounceKeywords = [
            'delivery incomplete',
            'undeliverable',
            'undelivered mail returned to sender',
            'mail delivery failed',
            'delivery status notification (failure)',
            'delivery failure',
            'returned mail',
            'failure notice'
        ];

        foreach ($bounceKeywords as $keyword) {
            if (str_contains($subject, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract the recipient email address that bounced.
     */
    public function extractBouncedEmail(array $messageData): ?string
    {
        $content = ($messageData['subject'] ?? '') . "\n" . ($messageData['body_text'] ?? '') . "\n" . ($messageData['body_html'] ?? '');

        // 1. Try explicit header patterns if present
        if (!empty($messageData['failed_recipient'])) {
            return strtolower(trim($messageData['failed_recipient']));
        }

        // 2. Common bounce phrases in body text
        $patterns = [
            '/delivering your message to\s+([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i',
            '/failed delivery to:\s*([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i',
            '/recipient\s+([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})\s+was not reached/i',
            '/<([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})>:\s*host/i',
            '/To:\s*<([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})>/i',
            '/To:\s*([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $email = strtolower(trim($matches[1]));
                // Ignore the sender's own domain or mailer-daemon
                if (!str_contains($email, 'mailer-daemon') && !str_contains($email, 'postmaster')) {
                    return $email;
                }
            }
        }

        return null;
    }

    /**
     * Process a detected bounce and update CampaignLead and main Business records.
     */
    public function processBounce(string $bouncedEmail, ?string $reason = null, ?int $senderId = null): array
    {
        $bouncedEmail = strtolower(trim($bouncedEmail));
        if (empty($bouncedEmail)) {
            return ['success' => false, 'message' => 'Empty email address provided.'];
        }

        $now = now();
        $updatedLeadsCount = 0;
        $updatedBusinessesCount = 0;

        DB::transaction(function () use ($bouncedEmail, $reason, $now, &$updatedLeadsCount, &$updatedBusinessesCount) {
            // 1. Update main Business lead records
            $businesses = Business::whereRaw('LOWER(email) = ?', [$bouncedEmail])->get();
            foreach ($businesses as $business) {
                $business->update([
                    'is_bounced' => true,
                    'bounced_at' => $now,
                    'lead_status' => 'bounced',
                ]);

                $userId = auth()->id() ?? $business->assigned_user_id ?? \App\Models\User::first()?->id;
                if ($userId) {
                    LeadActivity::create([
                        'business_id' => $business->id,
                        'user_id' => $userId,
                        'activity_type' => 'status_changed',
                        'status' => null,
                        'comment' => 'Email delivery failed (Bounce detected): ' . ($reason ?? 'Delivery Incomplete/Failed'),
                        'meta' => [
                            'bounced_email' => $bouncedEmail,
                            'bounced_at' => $now->toDateTimeString(),
                            'reason' => $reason,
                        ],
                    ]);
                }

                $updatedBusinessesCount++;
            }

            // 2. Update Campaign Leads
            $campaignLeads = CampaignLead::whereHas('business', function ($q) use ($bouncedEmail) {
                $q->whereRaw('LOWER(email) = ?', [$bouncedEmail]);
            })->get();

            foreach ($campaignLeads as $campaignLead) {
                if ($campaignLead->status !== 'bounced') {
                    $campaignLead->update([
                        'status' => 'bounced',
                        'bounced_at' => $now,
                        'failure_reason' => $reason ?? 'Email delivery bounced',
                    ]);

                    // Increment bounced_count on EmailCampaign
                    if ($campaignLead->email_campaign_id) {
                        EmailCampaign::where('id', $campaignLead->email_campaign_id)
                            ->increment('bounced_count');
                    }

                    $updatedLeadsCount++;
                }
            }
        });

        Log::info("Bounce processed for {$bouncedEmail}: Updated {$updatedBusinessesCount} businesses and {$updatedLeadsCount} campaign leads.");

        return [
            'success' => true,
            'email' => $bouncedEmail,
            'businesses_updated' => $updatedBusinessesCount,
            'campaign_leads_updated' => $updatedLeadsCount,
        ];
    }
}
