<?php

namespace App\Console\Commands;

use App\Models\EmailSender;
use App\Services\Email\Inbox\ImapSyncService;
use Illuminate\Console\Command;

class SyncBouncesCommand extends Command
{
    protected $signature = 'emails:sync-bounces {--sender= : ID of specific EmailSender}';
    protected $description = 'Sync email inboxes and process bounce notifications from mailer-daemon';

    public function handle(ImapSyncService $syncService): int
    {
        $this->info('Starting Bounce Inbox Sync...');

        $query = EmailSender::where('is_active', true);
        if ($senderId = $this->option('sender')) {
            $query->where('id', $senderId);
        }

        $senders = $query->get();
        if ($senders->isEmpty()) {
            $this->warn('No active email senders found.');
            return 0;
        }

        $totalSynced = 0;
        $totalBounces = 0;

        foreach ($senders as $sender) {
            $this->line("Syncing sender #{$sender->id} ({$sender->email})...");
            $result = $syncService->syncSender($sender);

            if ($result['success']) {
                $synced = $result['synced'] ?? 0;
                $bounces = $result['bounces'] ?? 0;
                $totalSynced += $synced;
                $totalBounces += $bounces;
                $this->info(" -> Synced: {$synced} messages, Bounces processed: {$bounces}");
            } else {
                $this->error(" -> Error: " . ($result['error'] ?? 'Sync failed'));
            }
        }

        $this->info("Bounce Inbox Sync complete. Total Synced: {$totalSynced}, Total Bounces Processed: {$totalBounces}.");
        return 0;
    }
}
