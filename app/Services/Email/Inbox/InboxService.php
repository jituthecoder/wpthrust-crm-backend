<?php

namespace App\Services\Email\Inbox;

use App\Models\Business;
use App\Models\CampaignLead;
use App\Models\EmailSender;
use App\Models\InboxMessage;
use App\Services\Email\EmailSenderService;
use App\Services\Email\ProviderFactory;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;

class InboxService
{
    protected EmailSenderService $senderService;

    public function __construct(EmailSenderService $senderService)
    {
        $this->senderService = $senderService;
    }

    /**
     * Get paginated messages with filters.
     */
    public function listMessages(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = InboxMessage::with(['emailSender', 'business', 'campaignLead']);

        // Filter by email sender account
        if (!empty($filters['email_sender_id'])) {
            $query->where('email_sender_id', $filters['email_sender_id']);
        }

        // Filter by folder (default: inbox)
        $folder = $filters['folder'] ?? 'inbox';
        if ($folder === 'starred') {
            $query->where('is_starred', true);
        } else {
            $query->where('folder', $folder);
        }

        // Filter by unread
        if (!empty($filters['unread_only']) && filter_var($filters['unread_only'], FILTER_VALIDATE_BOOLEAN)) {
            $query->where('is_read', false);
        }

        // Search in subject, body, or email addresses
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'LIKE', "%{$search}%")
                  ->orWhere('from_email', 'LIKE', "%{$search}%")
                  ->orWhere('to_email', 'LIKE', "%{$search}%")
                  ->orWhere('body_text', 'LIKE', "%{$search}%");
            });
        }

        return $query->latest('received_at')->paginate($perPage);
    }

    /**
     * Get list of sender accounts with unread message counts.
     */
    public function getSendersWithUnreadCount()
    {
        return EmailSender::select('id', 'name', 'display_name', 'email', 'provider', 'is_active')
            ->withCount(['inboxMessages as unread_count' => function ($q) {
                $q->where('folder', 'inbox')->where('is_read', false);
            }])
            ->get();
    }

    /**
     * Get conversation thread messages.
     */
    public function getThread(?string $threadId)
    {
        if (empty($threadId) || !trim($threadId)) {
            return collect();
        }

        $threadId = trim($threadId);

        return InboxMessage::with(['emailSender', 'business'])
            ->where(function ($q) use ($threadId) {
                $q->where('thread_id', $threadId)
                  ->orWhere('message_id', $threadId)
                  ->orWhere('in_reply_to', $threadId);
            })
            ->orderBy('received_at', 'asc')
            ->get();
    }

    /**
     * Send a new message from a selected sender account.
     */
    public function sendMessage(EmailSender $sender, array $data): InboxMessage
    {
        $senderAccount = $sender->senderAccount;
        if (!$senderAccount) {
            throw new \InvalidArgumentException("Sender account configuration missing.");
        }

        $toEmail = strtolower(trim($data['to_email']));
        $toName = $data['to_name'] ?? '';
        $subject = $data['subject'] ?? '(No Subject)';
        $bodyHtml = $data['body_html'] ?? $data['body'] ?? '';
        $bodyText = strip_tags($bodyHtml);

        // Send via ProviderFactory
        $provider = ProviderFactory::make($sender);
        
        // Prepare simple Mailable wrapper or direct send
        $mailable = new class($subject, $bodyHtml, $sender->email, $sender->display_name ?? $sender->name, $toEmail, $toName) extends \Illuminate\Mail\Mailable {
            public string $htmlContent;
            public function __construct($subject, $htmlContent, $fromEmail, $fromName, $toEmail, $toName)
            {
                $this->subject = $subject;
                $this->htmlContent = $htmlContent;
                $this->from = [['address' => $fromEmail, 'name' => $fromName]];
                $this->to = [['address' => $toEmail, 'name' => $toName]];
            }
            public function build()
            {
                return $this->html($this->htmlContent);
            }
        };

        $sendSettings = array_merge($senderAccount->settings ?? [], [
            'in_reply_to' => $data['in_reply_to'] ?? null,
            'thread_id' => $data['thread_id'] ?? null,
        ]);

        $result = $provider->send($sendSettings, $mailable);

        if (!$result->isSuccess()) {
            throw new \RuntimeException($result->getErrorMessage() ?? 'Failed to send message.');
        }

        $messageId = $result->getProviderMessageId() ?? ('sent-' . uniqid() . '@' . parse_url(config('app.url'), PHP_URL_HOST));
        $threadId = $result->getProviderThreadId() ?? $messageId;

        // Match business
        $business = Business::whereRaw('LOWER(email) = ?', [$toEmail])->first();

        // Create sent message record
        return InboxMessage::create([
            'email_sender_id' => $sender->id,
            'business_id' => $business?->id,
            'organization_id' => $sender->organization_id,
            'message_id' => $messageId,
            'thread_id' => $threadId,
            'folder' => 'sent',
            'from_email' => strtolower($sender->email),
            'from_name' => $sender->display_name ?? $sender->name,
            'to_email' => $toEmail,
            'to_name' => $toName,
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText,
            'snippet' => substr($bodyText, 0, 500),
            'is_read' => true,
            'received_at' => now(),
        ]);
    }

    /**
     * Reply to an existing inbox message thread.
     */
    public function replyToMessage(InboxMessage $originalMessage, array $data): InboxMessage
    {
        $sender = $originalMessage->emailSender;
        if (!$sender) {
            throw new \InvalidArgumentException("Associated sender account not found.");
        }

        $subject = $data['subject'] ?? ('Re: ' . preg_replace('/^Re:\s*/i', '', $originalMessage->subject));
        $bodyHtml = $data['body_html'] ?? $data['body'] ?? '';

        $sendData = [
            'to_email' => $originalMessage->from_email,
            'to_name' => $originalMessage->from_name,
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'in_reply_to' => $originalMessage->message_id,
            'thread_id' => $originalMessage->thread_id ?? $originalMessage->message_id,
        ];

        $reply = $this->sendMessage($sender, $sendData);
        $reply->update([
            'in_reply_to' => $originalMessage->message_id,
            'thread_id' => $originalMessage->thread_id ?? $originalMessage->message_id,
        ]);

        return $reply;
    }

    /**
     * Mark message as read / unread.
     */
    public function markRead(InboxMessage $message, bool $isRead = true): bool
    {
        return $message->update(['is_read' => $isRead]);
    }

    /**
     * Toggle starred flag on message.
     */
    public function toggleStar(InboxMessage $message): bool
    {
        return $message->update(['is_starred' => !$message->is_starred]);
    }

    /**
     * Delete message (move to trash).
     */
    public function deleteMessage(InboxMessage $message): bool
    {
        if ($message->folder === 'trash') {
            return $message->delete();
        }
        return $message->update(['folder' => 'trash']);
    }
}
