<?php

namespace App\Console\Commands;

use App\Models\InboxMessage;
use App\Services\Email\Inbox\ImapSyncService;
use Illuminate\Console\Command;

class DeduplicateInboxCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inbox:deduplicate {--sender= : Optional EmailSender ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find and remove duplicate email messages in the inbox_messages table.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $senderId = $this->option('sender') ? (int) $this->option('sender') : null;
        $this->info("Scanning inbox_messages table for duplicates" . ($senderId ? " for sender #{$senderId}" : "") . "...");

        $deletedCount = ImapSyncService::deduplicateMessages($senderId);

        $this->info("Deduplication complete! Removed {$deletedCount} duplicate message(s).");
        return 0;
    }
}
