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
            $table->unsignedBigInteger('business_id')->nullable()->change();
            $table->unsignedBigInteger('contact_list_lead_id')->nullable()->after('business_id')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaign_leads', function (Blueprint $table) {
            $table->dropColumn('contact_list_lead_id');
        });
    }
};
