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
        Schema::table('campaign_leads', function (Blueprint $table) {
            // Composite index for finding due pending leads
            $table->index(['status', 'scheduled_at'], 'idx_leads_status_scheduled');

            // Composite index for campaign-specific status queries
            $table->index(['email_campaign_id', 'status'], 'idx_leads_campaign_status');

            // Composite index for sender-specific status queries
            $table->index(['email_sender_id', 'status'], 'idx_leads_sender_status');

            // Composite index for crash recovery of stuck processing leads
            $table->index(['status', 'processing_started_at'], 'idx_leads_status_processing_started');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaign_leads', function (Blueprint $table) {
            $table->dropIndex('idx_leads_status_scheduled');
            $table->dropIndex('idx_leads_campaign_status');
            $table->dropIndex('idx_leads_sender_status');
            $table->dropIndex('idx_leads_status_processing_started');
        });
    }
};
