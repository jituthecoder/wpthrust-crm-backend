<?php

namespace App\Console\Commands;

use App\Models\EmailSender;
use App\Services\Email\Inbox\ImapSyncService;
use Illuminate\Console\Command;

class SyncInboxCommand extends Command
{
    protected $signature = 'inbox:sync {--sender= : ID of specific EmailSender}';
    protected $description = 'Sync incoming emails across all active sender accounts into the unified inbox';

    public function handle(ImapSyncService $syncService): int
    {
        $this->info('Starting Unified Inbox Sync...');

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
        foreach ($senders as $sender) {
            $result = $syncService->syncSender($sender);
            if ($result['success']) {
                $synced = $result['synced'] ?? 0;
                $totalSynced += $synced;
            }
        }

        $this->info("Unified Inbox Sync complete. Synced {$totalSynced} messages.");
        return 0;
    }
}
