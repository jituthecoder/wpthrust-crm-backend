<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_senders', function (Blueprint $table) {
            if (!Schema::hasColumn('email_senders', 'error_message')) {
                $table->text('error_message')->nullable()->after('is_active');
            }
            if (!Schema::hasColumn('email_senders', 'requires_reauth')) {
                $table->boolean('requires_reauth')->default(false)->after('error_message');
            }
        });
    }

    public function down(): void
    {
        Schema::table('email_senders', function (Blueprint $table) {
            if (Schema::hasColumn('email_senders', 'error_message')) {
                $table->dropColumn('error_message');
            }
            if (Schema::hasColumn('email_senders', 'requires_reauth')) {
                $table->dropColumn('requires_reauth');
            }
        });
    }
};
