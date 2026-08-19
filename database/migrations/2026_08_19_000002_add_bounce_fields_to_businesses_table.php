<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            if (!Schema::hasColumn('businesses', 'is_bounced')) {
                $table->boolean('is_bounced')->default(false)->after('lead_status')->index();
            }
            if (!Schema::hasColumn('businesses', 'bounced_at')) {
                $table->timestamp('bounced_at')->nullable()->after('is_bounced')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            if (Schema::hasColumn('businesses', 'bounced_at')) {
                $table->dropColumn('bounced_at');
            }
            if (Schema::hasColumn('businesses', 'is_bounced')) {
                $table->dropColumn('is_bounced');
            }
        });
    }
};
