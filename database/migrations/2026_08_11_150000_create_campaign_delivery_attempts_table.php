<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('campaign_delivery_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->foreignId('email_campaign_id')->constrained('email_campaigns')->onDelete('cascade');
            $table->foreignId('campaign_lead_id')->constrained('campaign_leads')->onDelete('cascade');
            $table->foreignId('email_sender_id')->nullable()->constrained('email_senders')->onDelete('set null');

            $table->unsignedInteger('attempt_number')->default(1);
            $table->string('idempotency_key', 64)->unique();
            $table->string('status', 32)->default('pending'); // pending, sending, sent, failed, unknown

            $table->string('provider_message_id')->nullable();
            $table->string('provider_thread_id')->nullable();
            $table->text('failure_reason')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['campaign_lead_id', 'attempt_number'], 'cda_lead_attempt_unique');
            $table->index(['organization_id', 'status'], 'cda_org_status_idx');
            $table->index(['email_campaign_id', 'status'], 'cda_camp_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_delivery_attempts');
    }
};
