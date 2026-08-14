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
        Schema::table('email_campaigns', function (Blueprint $table) {
            if (!Schema::hasColumn('email_campaigns', 'auto_sync_enabled')) {
                $table->boolean('auto_sync_enabled')->default(false)->after('status');
            }
            if (!Schema::hasColumn('email_campaigns', 'auto_sync_criteria')) {
                $table->json('auto_sync_criteria')->nullable()->after('auto_sync_enabled');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_campaigns', function (Blueprint $table) {
            if (Schema::hasColumn('email_campaigns', 'auto_sync_criteria')) {
                $table->dropColumn('auto_sync_criteria');
            }
            if (Schema::hasColumn('email_campaigns', 'auto_sync_enabled')) {
                $table->dropColumn('auto_sync_enabled');
            }
        });
    }
};
