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
        Schema::create('campaign_leads', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Relationships
            |--------------------------------------------------------------------------
            */

            $table->foreignId('email_campaign_id')
                ->constrained('email_campaigns')
                ->cascadeOnDelete();

            $table->foreignId('business_id')
                ->constrained('businesses')
                ->cascadeOnDelete();

            $table->foreignId('email_sender_id')
                ->nullable()
                ->constrained('email_senders')
                ->nullOnDelete();

            $table->foreignId('email_template_version_id')
                ->nullable()
                ->constrained('email_template_versions')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Campaign Status
            |--------------------------------------------------------------------------
            */

            $table->enum('status', [

                'pending',

                'processing',

                'sent',

                'failed',

                'bounced',

                'opened',

                'clicked',

                'replied',

                'unsubscribed',

                'skipped',

            ])->default('pending');

            /*
            |--------------------------------------------------------------------------
            | Retry
            |--------------------------------------------------------------------------
            */

            $table->unsignedTinyInteger('retry_count')
                ->default(0);

            $table->unsignedTinyInteger('max_retry')
                ->default(3);

            /*
            |--------------------------------------------------------------------------
            | Provider
            |--------------------------------------------------------------------------
            */

            $table->string('provider_message_id')->nullable();

            $table->string('provider_thread_id')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Scheduling
            |--------------------------------------------------------------------------
            */

            $table->timestamp('scheduled_at')->nullable();

            $table->timestamp('processing_started_at')->nullable();

            $table->timestamp('last_attempt_at')->nullable();

            $table->timestamp('sent_at')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Failure
            |--------------------------------------------------------------------------
            */

            $table->text('failure_reason')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Tracking
            |--------------------------------------------------------------------------
            */

            $table->timestamp('opened_at')->nullable();

            $table->timestamp('clicked_at')->nullable();

            $table->timestamp('replied_at')->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->unique([
                'email_campaign_id',
                'business_id'
            ]);

            $table->index('status');

            $table->index('scheduled_at');

            $table->index('email_sender_id');

            $table->index('provider_thread_id');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_leads');
    }
};