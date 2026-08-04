<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_audits', function (Blueprint $table) {

            $table->id();

            $table->foreignId('business_id')
                ->constrained('businesses')
                ->cascadeOnDelete();

            $table->string('average_rating')->nullable();
            $table->integer('review_count')->default(0);

            // Mobile
            $table->string('mobile_pagespeed')->nullable();
            $table->string('mobile_seo')->nullable();
            $table->string('mobile_accessibility')->nullable();
            $table->string('mobile_best_practices')->nullable();
            $table->string('mobile_load_time')->nullable();
            $table->string('mobile_lcp')->nullable();
            $table->string('mobile_tbt')->nullable();

            // Desktop
            $table->string('desktop_pagespeed')->nullable();
            $table->string('desktop_seo')->nullable();
            $table->string('desktop_accessibility')->nullable();
            $table->string('desktop_best_practices')->nullable();
            $table->string('desktop_load_time')->nullable();
            $table->string('desktop_lcp')->nullable();
            $table->string('desktop_tbt')->nullable();

            // Social
            $table->boolean('contact_form')->default(false);

            $table->text('facebook')->nullable();
            $table->text('instagram')->nullable();
            $table->text('linkedin')->nullable();

            $table->timestamps();

            $table->index('business_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_audits');
    }
};