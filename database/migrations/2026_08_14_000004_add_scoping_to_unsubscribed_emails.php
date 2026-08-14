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
        Schema::table('unsubscribed_emails', function (Blueprint $table) {
            if (!Schema::hasColumn('unsubscribed_emails', 'organization_id')) {
                $table->foreignId('organization_id')->nullable()->after('email')->constrained('organizations')->onDelete('cascade');
            }
            if (!Schema::hasColumn('unsubscribed_emails', 'campaign_id')) {
                $table->foreignId('campaign_id')->nullable()->after('organization_id')->constrained('email_campaigns')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unsubscribed_emails', function (Blueprint $table) {
            if (Schema::hasColumn('unsubscribed_emails', 'organization_id')) {
                $table->dropForeign(['organization_id']);
                $table->dropColumn('organization_id');
            }
            if (Schema::hasColumn('unsubscribed_emails', 'campaign_id')) {
                $table->dropForeign(['campaign_id']);
                $table->dropColumn('campaign_id');
            }
        });
    }
};
