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
        Schema::table('business_audits', function (Blueprint $table) {
            $table->string('mobile_fcp')->nullable()->after('mobile_pagespeed');
            $table->string('mobile_cls')->nullable()->after('mobile_tbt');
            $table->string('mobile_speed_index')->nullable()->after('mobile_cls');
            $table->string('mobile_screenshot_path')->nullable()->after('mobile_speed_index');
            $table->enum('psi_status', ['pending', 'processing', 'completed', 'failed'])->default('pending')->after('mobile_screenshot_path');
            $table->text('psi_error_reason')->nullable()->after('psi_status');
            $table->timestamp('psi_fetched_at')->nullable()->after('psi_error_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_audits', function (Blueprint $table) {
            $table->dropColumn([
                'mobile_fcp',
                'mobile_cls',
                'mobile_speed_index',
                'mobile_screenshot_path',
                'psi_status',
                'psi_error_reason',
                'psi_fetched_at',
            ]);
        });
    }
};
