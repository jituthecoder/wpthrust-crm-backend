<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\InboxMessage;
use Illuminate\Support\Facades\DB;

$duplicates = InboxMessage::select('subject', 'from_email', DB::raw('COUNT(*) as count'))
    ->groupBy('subject', 'from_email')
    ->having('count', '>', 1)
    ->get();

echo "DUPLICATE GROUPS COUNT: " . $duplicates->count() . "\n";
foreach ($duplicates as $dup) {
    echo "SUBJECT: {$dup->subject} | FROM: {$dup->from_email} | COUNT: {$dup->count}\n";
}

$allMessages = InboxMessage::select('id', 'email_sender_id', 'message_id', 'thread_id', 'from_email', 'subject', 'created_at')
    ->orderBy('id', 'desc')
    ->limit(20)
    ->get();

echo "\nLAST 20 MESSAGES:\n";
foreach ($allMessages as $m) {
    echo "ID: {$m->id} | SENDER_ID: {$m->email_sender_id} | MSG_ID: " . substr($m->message_id, 0, 30) . " | THREAD_ID: " . substr($m->thread_id, 0, 30) . " | FROM: {$m->from_email} | SUBJ: {$m->subject}\n";
}
