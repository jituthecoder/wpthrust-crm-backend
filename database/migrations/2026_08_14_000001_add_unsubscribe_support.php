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
        // 1. Create global organization-scoped unsubscribed_emails table
        if (!Schema::hasTable('unsubscribed_emails')) {
            Schema::create('unsubscribed_emails', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained()->onDelete('cascade');
                $table->string('email')->index();
                $table->unsignedBigInteger('campaign_lead_id')->nullable();
                $table->timestamp('unsubscribed_at');
                $table->timestamps();

                $table->unique(['organization_id', 'email']);
            });
        }

        // 2. Add unsubscribe token and timestamp to campaign_leads table
        Schema::table('campaign_leads', function (Blueprint $table) {
            $table->string('status', 32)->default('pending')->change();
            if (!Schema::hasColumn('campaign_leads', 'unsubscribe_token')) {
                $table->string('unsubscribe_token', 64)->nullable()->unique()->after('status');
            }
            if (!Schema::hasColumn('campaign_leads', 'unsubscribed_at')) {
                $table->timestamp('unsubscribed_at')->nullable()->after('sent_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaign_leads', function (Blueprint $table) {
            if (Schema::hasColumn('campaign_leads', 'unsubscribed_at')) {
                $table->dropColumn('unsubscribed_at');
            }
            if (Schema::hasColumn('campaign_leads', 'unsubscribe_token')) {
                $table->dropColumn('unsubscribe_token');
            }
        });

        Schema::dropIfExists('unsubscribed_emails');
    }
};
