<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailSender;
use App\Models\InboxMessage;
use App\Services\Email\Inbox\ImapSyncService;
use App\Services\Email\Inbox\InboxService;
use Illuminate\Http\Request;

class InboxController extends Controller
{
    protected InboxService $inboxService;
    protected ImapSyncService $syncService;

    public function __construct(InboxService $inboxService, ImapSyncService $syncService)
    {
        $this->inboxService = $inboxService;
        $this->syncService = $syncService;
    }

    /**
     * List Inbox Messages
     */
    public function index(Request $request)
    {
        $filters = $request->only(['email_sender_id', 'folder', 'unread_only', 'search']);
        $perPage = (int) $request->input('per_page', 25);

        $messages = $this->inboxService->listMessages($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }

    /**
     * Get Senders with Unread Count Badges
     */
    public function senders()
    {
        $senders = $this->inboxService->getSendersWithUnreadCount();

        return response()->json([
            'success' => true,
            'data' => $senders,
        ]);
    }

    /**
     * Get Conversation Thread
     */
    public function thread(string $threadId)
    {
        $messages = $this->inboxService->getThread($threadId);

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }

    /**
     * Show Single Message
     */
    public function show(InboxMessage $inboxMessage)
    {
        // Mark as read when opened
        if (!$inboxMessage->is_read) {
            $this->inboxService->markRead($inboxMessage, true);
        }

        return response()->json([
            'success' => true,
            'data' => $inboxMessage->load(['emailSender', 'business', 'campaignLead']),
        ]);
    }

    /**
     * Compose & Send New Email
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'email_sender_id' => 'required|exists:email_senders,id',
            'to_email' => 'required|email',
            'to_name' => 'nullable|string|max:255',
            'subject' => 'nullable|string|max:500',
            'body_html' => 'required|string',
        ]);

        $sender = EmailSender::findOrFail($validated['email_sender_id']);
        $message = $this->inboxService->sendMessage($sender, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Email sent successfully.',
            'data' => $message,
        ], 201);
    }

    /**
     * Reply to Message Thread
     */
    public function reply(Request $request, InboxMessage $inboxMessage)
    {
        $validated = $request->validate([
            'body_html' => 'required|string',
            'subject' => 'nullable|string|max:500',
        ]);

        $reply = $this->inboxService->replyToMessage($inboxMessage, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Reply sent successfully.',
            'data' => $reply,
        ]);
    }

    /**
     * Toggle Mark Read / Unread
     */
    public function markRead(Request $request, InboxMessage $inboxMessage)
    {
        $isRead = $request->boolean('is_read', true);
        $this->inboxService->markRead($inboxMessage, $isRead);

        return response()->json([
            'success' => true,
            'message' => $isRead ? 'Marked as read.' : 'Marked as unread.',
            'data' => $inboxMessage,
        ]);
    }

    /**
     * Toggle Star Message
     */
    public function toggleStar(InboxMessage $inboxMessage)
    {
        $this->inboxService->toggleStar($inboxMessage);

        return response()->json([
            'success' => true,
            'message' => $inboxMessage->is_starred ? 'Starred.' : 'Unstarred.',
            'data' => $inboxMessage,
        ]);
    }

    /**
     * Delete Message
     */
    public function destroy(InboxMessage $inboxMessage)
    {
        $this->inboxService->deleteMessage($inboxMessage);

        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully.',
        ]);
    }

    /**
     * Trigger Manual Sync for Senders
     */
    public function sync(Request $request)
    {
        $senderId = $request->input('email_sender_id');
        $senders = EmailSender::where('is_active', true);

        if (!empty($senderId)) {
            $senders->where('id', $senderId);
        } else {
            // Limit batch sync to max 5 active senders per sync to prevent timeouts
            $senders->limit(5);
        }

        $senders = $senders->get();

        if ($senders->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No active sender account found to sync.',
                'data' => ['synced' => 0, 'bounces' => 0],
            ]);
        }

        $synced = 0;
        $bounces = 0;
        $syncedSenders = [];

        foreach ($senders as $sender) {
            $res = $this->syncService->syncSender($sender);
            if ($res['success']) {
                $synced += ($res['synced'] ?? 0);
                $bounces += ($res['bounces'] ?? 0);
                $syncedSenders[] = $sender->email;
            }
        }

        $targetName = (count($syncedSenders) === 1) ? $syncedSenders[0] : (count($syncedSenders) . ' active senders');
        $msg = ($synced > 0 || $bounces > 0)
            ? "Synced {$synced} new message(s) for {$targetName}."
            : "Mailbox for {$targetName} is up to date.";

        return response()->json([
            'success' => true,
            'message' => $msg,
            'data' => [
                'synced' => $synced,
                'bounces' => $bounces,
                'senders' => $syncedSenders,
            ],
        ]);
    }
}
