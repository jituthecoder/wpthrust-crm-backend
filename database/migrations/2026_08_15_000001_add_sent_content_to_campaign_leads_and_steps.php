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
            if (!Schema::hasColumn('campaign_leads', 'sent_subject')) {
                $table->text('sent_subject')->nullable()->after('status');
            }
            if (!Schema::hasColumn('campaign_leads', 'sent_body_html')) {
                $table->longText('sent_body_html')->nullable()->after('sent_subject');
            }
        });

        Schema::table('campaign_delivery_attempts', function (Blueprint $table) {
            if (!Schema::hasColumn('campaign_delivery_attempts', 'sent_subject')) {
                $table->text('sent_subject')->nullable()->after('status');
            }
            if (!Schema::hasColumn('campaign_delivery_attempts', 'sent_body_html')) {
                $table->longText('sent_body_html')->nullable()->after('sent_subject');
            }
        });

        if (Schema::hasTable('campaign_lead_steps')) {
            Schema::table('campaign_lead_steps', function (Blueprint $table) {
                if (!Schema::hasColumn('campaign_lead_steps', 'sent_subject')) {
                    $table->text('sent_subject')->nullable()->after('status');
                }
                if (!Schema::hasColumn('campaign_lead_steps', 'sent_body_html')) {
                    $table->longText('sent_body_html')->nullable()->after('sent_subject');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaign_leads', function (Blueprint $table) {
            if (Schema::hasColumn('campaign_leads', 'sent_subject')) {
                $table->dropColumn(['sent_subject', 'sent_body_html']);
            }
        });

        Schema::table('campaign_delivery_attempts', function (Blueprint $table) {
            if (Schema::hasColumn('campaign_delivery_attempts', 'sent_subject')) {
                $table->dropColumn(['sent_subject', 'sent_body_html']);
            }
        });

        if (Schema::hasTable('campaign_lead_steps')) {
            Schema::table('campaign_lead_steps', function (Blueprint $table) {
                if (Schema::hasColumn('campaign_lead_steps', 'sent_subject')) {
                    $table->dropColumn(['sent_subject', 'sent_body_html']);
                }
            });
        }
    }
};
