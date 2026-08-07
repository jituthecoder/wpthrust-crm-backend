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
        Schema::create('campaign_senders', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Relationships
            |--------------------------------------------------------------------------
            */

            $table->foreignId('email_campaign_id')
                ->constrained('email_campaigns')
                ->cascadeOnDelete();

            $table->foreignId('email_sender_id')
                ->constrained('email_senders')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Sending Configuration
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger('priority')
                ->default(1);

            $table->unsignedInteger('weight')
                ->default(1);

            /*
            |--------------------------------------------------------------------------
            | Override Limits
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger('daily_limit')
                ->nullable();

            $table->unsignedInteger('hourly_limit')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Statistics
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger('sent_count')
                ->default(0);

            $table->unsignedInteger('failed_count')
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            $table->boolean('is_active')
                ->default(true);

            $table->timestamp('last_sent_at')
                ->nullable();

            $table->timestamps();

            $table->unique([
                'email_campaign_id',
                'email_sender_id'
            ]);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_senders');
    }
};