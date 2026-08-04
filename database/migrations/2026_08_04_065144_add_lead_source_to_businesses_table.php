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
        Schema::table('businesses', function (Blueprint $table) {

            $table->enum('lead_source', [
                'google_maps',
                'manual',
                'website',
                'facebook_ads',
                'referral',
                'import',
                'other',
            ])
            ->default('google_maps')
            ->after('country')
            ->index();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {

            $table->dropColumn('lead_source');

        });
    }
};