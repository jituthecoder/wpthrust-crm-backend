<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_leads', function (Blueprint $table) {
            if (!Schema::hasColumn('campaign_leads', 'bounced_at')) {
                $table->timestamp('bounced_at')->nullable()->after('replied_at')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('campaign_leads', function (Blueprint $table) {
            if (Schema::hasColumn('campaign_leads', 'bounced_at')) {
                $table->dropColumn('bounced_at');
            }
        });
    }
};
