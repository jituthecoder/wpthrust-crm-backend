<?php

namespace App\Services\Email\Inbox;

use App\Models\Business;
use App\Models\CampaignLead;
use App\Models\EmailCampaign;
use App\Models\EmailSender;
use App\Models\InboxMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImapSyncService
{
    protected BounceParserService $bounceParser;

    public function __construct(BounceParserService $bounceParser)
    {
        $this->bounceParser = $bounceParser;
    }

    /**
     * Sync emails for a specific EmailSender account.
     */
    public function syncSender(EmailSender $sender, int $limit = 50): array
    {
        $provider = strtolower($sender->provider ?? 'smtp');
        $senderAccount = $sender->senderAccount;
        if (!$senderAccount) {
            return ['success' => false, 'message' => 'No account credentials found.'];
        }

        $settings = $senderAccount->settings ?? [];
        $syncedCount = 0;
        $bouncesCount = 0;

        try {
            if ($provider === 'gmail' && !empty($settings['refresh_token'] ?? $settings['access_token'] ?? null)) {
                $results = $this->syncViaGmailApi($sender, $settings, $limit);
                $syncedCount = $results['synced'];
                $bouncesCount = $results['bounces'];
            } elseif ($provider === 'outlook' && !empty($settings['refresh_token'] ?? $settings['access_token'] ?? null)) {
                $results = $this->syncViaOutlookApi($sender, $settings, $limit);
                $syncedCount = $results['synced'];
                $bouncesCount = $results['bounces'];
            } else {
                // Generic IMAP sync for SMTP / Custom accounts
                $results = $this->syncViaImap($sender, $settings, $limit);
                $syncedCount = $results['synced'];
                $bouncesCount = $results['bounces'];
            }

            $sender->update(['last_sync_at' => now()]);

            return [
                'success' => true,
                'sender_id' => $sender->id,
                'synced' => $syncedCount,
                'bounces' => $bouncesCount,
            ];
        } catch (Throwable $e) {
            Log::error("IMAP Sync error for sender #{$sender->id} ({$sender->email}): " . $e->getMessage());
            return [
                'success' => false,
                'sender_id' => $sender->id,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Sync via Gmail REST API for Gmail accounts.
     */
    protected function syncViaGmailApi(EmailSender $sender, array $settings, int $limit): array
    {
        $gmailProvider = new \App\Services\Email\Providers\GmailProvider();
        $accessToken = $gmailProvider->getValidAccessToken($settings, $sender->id);

        if (empty($accessToken)) {
            throw new \Exception("Gmail OAuth access token unavailable.");
        }

        // List messages (retrieve inbox, sent, and unread recent emails)
        $listRes = Http::withToken($accessToken)->get('https://gmail.googleapis.com/gmail/v1/users/me/messages', [
            'maxResults' => $limit,
            'q' => 'in:inbox OR in:sent OR is:unread',
        ]);

        if (!$listRes->successful()) {
            return ['synced' => 0, 'bounces' => 0];
        }

        $messages = $listRes->json('messages') ?? [];
        $synced = 0;
        $bounces = 0;

        foreach ($messages as $msg) {
            $msgId = $msg['id'];
            // Check if already stored
            if (InboxMessage::where('message_id', $msgId)->exists()) {
                continue;
            }

            $detailRes = Http::withToken($accessToken)->get("https://gmail.googleapis.com/gmail/v1/users/me/messages/{$msgId}", [
                'format' => 'full',
            ]);

            if (!$detailRes->successful()) {
                continue;
            }

            $msgData = $detailRes->json();
            $parsed = $this->parseGmailMessageData($msgData, $sender);
            
            $stored = $this->storeMessageAndProcess($sender, $parsed);
            if ($stored['is_bounce']) {
                $bounces++;
            }
            $synced++;
        }

        return ['synced' => $synced, 'bounces' => $bounces];
    }

    /**
     * Parse raw Gmail API response payload.
     */
    protected function parseGmailMessageData(array $msgData, EmailSender $sender): array
    {
        $headers = [];
        foreach ($msgData['payload']['headers'] ?? [] as $h) {
            $headers[strtolower($h['name'])] = $h['value'];
        }

        $from = $headers['from'] ?? '';
        $to = $headers['to'] ?? '';
        $subject = $headers['subject'] ?? '';
        $messageId = $headers['message-id'] ?? $msgData['id'];
        $inReplyTo = $headers['in-reply-to'] ?? null;
        $threadId = $msgData['threadId'] ?? null;
        $snippet = $msgData['snippet'] ?? '';

        // Extract email & name
        $fromEmail = $from;
        $fromName = '';
        if (preg_match('/^(.*?)\s*<(.*?)>$/', $from, $matches)) {
            $fromName = trim($matches[1], '"\' ');
            $fromEmail = trim($matches[2]);
        }

        $toEmail = $to;
        $toName = '';
        if (preg_match('/^(.*?)\s*<(.*?)>$/', $to, $matches)) {
            $toName = trim($matches[1], '"\' ');
            $toEmail = trim($matches[2]);
        }

        // Body extraction
        $bodyHtml = '';
        $bodyText = $snippet;
        $payload = $msgData['payload'] ?? [];
        
        if (!empty($payload['body']['data'])) {
            $bodyText = base64_decode(strtr($payload['body']['data'], '-_', '+/'));
        } elseif (!empty($payload['parts'])) {
            foreach ($payload['parts'] as $part) {
                if (($part['mimeType'] ?? '') === 'text/html' && !empty($part['body']['data'])) {
                    $bodyHtml = base64_decode(strtr($part['body']['data'], '-_', '+/'));
                } elseif (($part['mimeType'] ?? '') === 'text/plain' && !empty($part['body']['data'])) {
                    $bodyText = base64_decode(strtr($part['body']['data'], '-_', '+/'));
                }
            }
        }

        return [
            'message_id' => $messageId,
            'in_reply_to' => $inReplyTo,
            'thread_id' => $threadId,
            'from_email' => strtolower($fromEmail),
            'from_name' => $fromName,
            'to_email' => strtolower($toEmail),
            'to_name' => $toName,
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText,
            'snippet' => substr($snippet, 0, 500),
            'received_at' => isset($msgData['internalDate']) ? date('Y-m-d H:i:s', (int)($msgData['internalDate'] / 1000)) : now(),
        ];
    }

    /**
     * Sync via Microsoft Graph REST API for Outlook accounts.
     */
    protected function syncViaOutlookApi(EmailSender $sender, array $settings, int $limit): array
    {
        $outlookProvider = new \App\Services\Email\Providers\OutlookProvider();
        $accessToken = $outlookProvider->getValidAccessToken($settings, $sender->id);

        if (empty($accessToken)) {
            return ['synced' => 0, 'bounces' => 0];
        }

        $response = Http::withToken($accessToken)
            ->withHeaders(['Prefer' => 'IdType="ImmutableId"'])
            ->get('https://graph.microsoft.com/v1.0/me/messages', [
                '$top' => $limit,
                '$orderby' => 'receivedDateTime desc',
            ]);

        if (!$response->successful()) {
            return ['synced' => 0, 'bounces' => 0];
        }

        $messages = $response->json('value') ?? [];
        $synced = 0;
        $bounces = 0;

        foreach ($messages as $msg) {
            $msgId = $msg['id'] ?? null;
            if (!$msgId || InboxMessage::where('message_id', $msgId)->exists()) {
                continue;
            }

            $fromEmail = strtolower($msg['from']['emailAddress']['address'] ?? '');
            $fromName = $msg['from']['emailAddress']['name'] ?? '';
            $toEmail = strtolower($msg['toRecipients'][0]['emailAddress']['address'] ?? $sender->email);
            $toName = $msg['toRecipients'][0]['emailAddress']['name'] ?? '';
            $subject = $msg['subject'] ?? '';
            $bodyHtml = $msg['body']['contentType'] === 'html' ? ($msg['body']['content'] ?? '') : null;
            $bodyText = $msg['bodyPreview'] ?? strip_tags($msg['body']['content'] ?? '');

            $parsed = [
                'message_id' => $msgId,
                'in_reply_to' => $msg['internetMessageId'] ?? null,
                'thread_id' => $msg['conversationId'] ?? null,
                'from_email' => $fromEmail,
                'from_name' => $fromName,
                'to_email' => $toEmail,
                'to_name' => $toName,
                'subject' => $subject,
                'body_html' => $bodyHtml,
                'body_text' => $bodyText,
                'snippet' => substr($bodyText, 0, 500),
                'received_at' => isset($msg['receivedDateTime']) ? date('Y-m-d H:i:s', strtotime($msg['receivedDateTime'])) : now(),
            ];

            $stored = $this->storeMessageAndProcess($sender, $parsed);
            if ($stored['is_bounce']) $bounces++;
            $synced++;
        }

        return ['synced' => $synced, 'bounces' => $bounces];
    }

    /**
     * Fallback IMAP sync using PHP native imap_open if available, or simulation layer.
     */
    protected function syncViaImap(EmailSender $sender, array $settings, int $limit): array
    {
        $host = $settings['imap_host'] ?? str_replace('smtp.', 'imap.', $settings['host'] ?? 'imap.' . substr(strrchr($sender->email, "@"), 1));
        $port = $settings['imap_port'] ?? 993;
        $username = $settings['username'] ?? $sender->email;
        $password = $settings['password'] ?? '';
        $encryption = $settings['encryption'] ?? 'ssl';

        if (function_exists('imap_open')) {
            $flags = "/imap/{$encryption}/validate-cert";
            $connectionString = "{" . $host . ":" . $port . $flags . "}INBOX";

            $mbox = @imap_open($connectionString, $username, $password, 0, 1);
            if (!$mbox) {
                // Try without cert validation
                $flags = "/imap/{$encryption}/novalidate-cert";
                $connectionString = "{" . $host . ":" . $port . $flags . "}INBOX";
                $mbox = @imap_open($connectionString, $username, $password, 0, 1);
            }

            if ($mbox) {
                $emails = imap_search($mbox, 'UNSEEN');
                if (!$emails) {
                    $emails = imap_search($mbox, 'ALL');
                }
                if ($emails) {
                    rsort($emails);
                    $emails = array_slice($emails, 0, $limit);

                    $synced = 0;
                    $bounces = 0;
                    foreach ($emails as $emailNum) {
                        $overview = imap_fetch_overview($mbox, $emailNum, 0);
                        if (empty($overview)) continue;
                        
                        $header = $overview[0];
                        $msgId = $header->message_id ?? "imap-{$sender->id}-{$emailNum}";

                        if (InboxMessage::where('message_id', $msgId)->exists()) {
                            continue;
                        }

                        $bodyRaw = imap_fetchbody($mbox, $emailNum, 1);
                        $bodyText = $this->decodeBody($bodyRaw);

                        $fromRaw = $header->fromaddress ?? $header->from ?? '';
                        if (str_contains($fromRaw, '=?')) {
                            $fromRaw = $this->decodeHeader($fromRaw);
                        }
                        $fromEmail = strtolower($fromRaw);
                        $fromName = '';
                        if (preg_match('/^(.*?)\s*<(.*?)>$/', $fromRaw, $matches)) {
                            $fromName = trim($matches[1], '"\' ');
                            $fromEmail = strtolower(trim($matches[2]));
                        }
                        if (preg_match('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/', $fromEmail, $m)) {
                            $fromEmail = strtolower($m[1]);
                        }

                        $subject = $this->decodeHeader($header->subject ?? '');

                        $parsed = [
                            'message_id' => $msgId,
                            'in_reply_to' => $header->in_reply_to ?? null,
                            'thread_id' => null,
                            'from_email' => $fromEmail,
                            'from_name' => $fromName ?: $fromEmail,
                            'to_email' => strtolower($sender->email),
                            'to_name' => $sender->name ?? '',
                            'subject' => $subject,
                            'body_text' => $bodyText,
                            'body_html' => null,
                            'snippet' => substr(trim(strip_tags($bodyText)), 0, 500),
                            'received_at' => isset($header->date) ? date('Y-m-d H:i:s', strtotime($header->date)) : now(),
                        ];

                        $stored = $this->storeMessageAndProcess($sender, $parsed);
                        if ($stored['is_bounce']) $bounces++;
                        $synced++;
                    }
                    imap_close($mbox);
                    return ['synced' => $synced, 'bounces' => $bounces];
                }
                imap_close($mbox);
            }
        }

        return ['synced' => 0, 'bounces' => 0];
    }

    /**
     * Store message record and process bounce / campaign reply linking.
     */
    protected function storeMessageAndProcess(EmailSender $sender, array $parsed): array
    {
        // Check for existing duplicate message
        $existing = null;
        if (!empty($parsed['message_id'])) {
            $existing = InboxMessage::where('message_id', $parsed['message_id'])->first();
        }
        if (!$existing && !empty($parsed['from_email']) && !empty($parsed['subject'])) {
            $existing = InboxMessage::where('email_sender_id', $sender->id)
                ->where('from_email', strtolower($parsed['from_email']))
                ->where('to_email', strtolower($parsed['to_email']))
                ->where('subject', $parsed['subject'])
                ->where('received_at', $parsed['received_at'] ?? now())
                ->first();
        }

        if ($existing) {
            return [
                'message' => $existing,
                'is_bounce' => ($existing->folder === 'bounce'),
            ];
        }

        $isBounce = $this->bounceParser->isBounceMessage($parsed);
        $isSent = (strtolower($parsed['from_email'] ?? '') === strtolower($sender->email));
        $folder = $isBounce ? 'bounce' : ($isSent ? 'sent' : 'inbox');

        $businessId = null;
        $campaignLeadId = null;

        if ($isBounce) {
            $bouncedEmail = $this->bounceParser->extractBouncedEmail($parsed);
            if ($bouncedEmail) {
                $this->bounceParser->processBounce($bouncedEmail, $parsed['subject'] ?? 'Delivery failure', $sender->id);
            }
        } else {
            // Find matching lead/business by email
            $business = Business::whereRaw('LOWER(email) = ?', [$parsed['from_email']])->first();
            if ($business) {
                $businessId = $business->id;

                // Match campaign lead
                $campaignLead = CampaignLead::where('business_id', $business->id)
                    ->whereIn('status', ['sent', 'opened', 'clicked'])
                    ->latest()
                    ->first();

                if ($campaignLead) {
                    $campaignLeadId = $campaignLead->id;
                    if (empty($campaignLead->replied_at)) {
                        $campaignLead->update(['replied_at' => now()]);
                        if ($campaignLead->email_campaign_id) {
                            EmailCampaign::where('id', $campaignLead->email_campaign_id)->increment('replied_count');
                        }
                    }
                }
            }
        }

        $message = InboxMessage::create([
            'email_sender_id' => $sender->id,
            'business_id' => $businessId,
            'campaign_lead_id' => $campaignLeadId,
            'organization_id' => $sender->organization_id,
            'message_id' => $parsed['message_id'] ?? null,
            'in_reply_to' => $parsed['in_reply_to'] ?? null,
            'thread_id' => $parsed['thread_id'] ?? null,
            'folder' => $folder,
            'from_email' => $parsed['from_email'],
            'from_name' => $parsed['from_name'] ?? null,
            'to_email' => $parsed['to_email'],
            'to_name' => $parsed['to_name'] ?? null,
            'subject' => $parsed['subject'] ?? '(No Subject)',
            'body_html' => $parsed['body_html'] ?? null,
            'body_text' => $parsed['body_text'] ?? null,
            'snippet' => $parsed['snippet'] ?? null,
            'is_read' => false,
            'is_starred' => false,
            'received_at' => $parsed['received_at'] ?? now(),
        ]);

        return [
            'message' => $message,
            'is_bounce' => $isBounce,
        ];
    }

    /**
     * Decode MIME headers like =?utf-8?Q?...?= into clean UTF-8 text.
     */
    protected function decodeHeader(?string $str): string
    {
        if (empty($str)) return '';
        if (str_contains($str, '=?')) {
            $decoded = @iconv_mime_decode($str, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
            if ($decoded) {
                return mb_convert_encoding($decoded, 'UTF-8', 'UTF-8');
            }
        }
        return mb_convert_encoding($str, 'UTF-8', 'UTF-8');
    }

    /**
     * Decode quoted-printable or base64 email bodies into clean UTF-8 text.
     */
    protected function decodeBody(?string $body): string
    {
        if (empty($body)) return '';
        if (str_contains($body, '=') || str_contains($body, '=E2') || str_contains($body, '=F0')) {
            $body = quoted_printable_decode($body);
        }
        return mb_convert_encoding($body, 'UTF-8', 'UTF-8');
    }
}
