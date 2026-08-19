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
        Schema::table('contact_list_leads', function (Blueprint $table) {
            $table->unsignedBigInteger('business_id')->nullable()->change();
            $table->string('business_name')->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->string('website')->nullable();
            $table->string('phone')->nullable();
            $table->string('category')->nullable();
            $table->string('country')->nullable();
            $table->integer('mobile_pagespeed')->nullable();
            $table->text('notes')->nullable();
            $table->json('custom_fields')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_list_leads', function (Blueprint $table) {
            $table->dropColumn([
                'business_name',
                'email',
                'website',
                'phone',
                'category',
                'country',
                'mobile_pagespeed',
                'notes',
                'custom_fields',
            ]);
        });
    }
};
