<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inbox_messages')) {
            Schema::create('inbox_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('email_sender_id')->constrained('email_senders')->onDelete('cascade');
                $table->foreignId('business_id')->nullable()->constrained('businesses')->onDelete('set null');
                $table->foreignId('campaign_lead_id')->nullable()->constrained('campaign_leads')->onDelete('set null');
                $table->foreignId('organization_id')->nullable()->constrained('organizations')->onDelete('cascade');

                $table->string('message_id', 255)->nullable()->index();
                $table->string('in_reply_to', 255)->nullable();
                $table->string('thread_id', 255)->nullable()->index();
                $table->string('folder', 50)->default('inbox')->index(); // inbox, sent, archive, trash, spam, bounce

                $table->string('from_email', 255)->index();
                $table->string('from_name', 255)->nullable();
                $table->string('to_email', 255)->index();
                $table->string('to_name', 255)->nullable();

                $table->string('subject', 500)->nullable();
                $table->longText('body_html')->nullable();
                $table->longText('body_text')->nullable();
                $table->string('snippet', 500)->nullable();

                $table->boolean('is_read')->default(false)->index();
                $table->boolean('is_starred')->default(false)->index();
                $table->timestamp('received_at')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inbox_messages');
    }
};
