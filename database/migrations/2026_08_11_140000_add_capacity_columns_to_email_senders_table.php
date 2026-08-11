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
        Schema::table('email_senders', function (Blueprint $table) {
            $table->unsignedInteger('reserved_today')->default(0)->after('sent_this_hour');
            $table->unsignedInteger('reserved_this_hour')->default(0)->after('reserved_today');
            $table->timestamp('last_daily_reset_at')->nullable()->after('reserved_this_hour');
            $table->timestamp('last_hourly_reset_at')->nullable()->after('last_daily_reset_at');
            $table->timestamp('last_reserved_at')->nullable()->after('last_hourly_reset_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_senders', function (Blueprint $table) {
            $table->dropColumn([
                'reserved_today',
                'reserved_this_hour',
                'last_daily_reset_at',
                'last_hourly_reset_at',
                'last_reserved_at',
            ]);
        });
    }
};
