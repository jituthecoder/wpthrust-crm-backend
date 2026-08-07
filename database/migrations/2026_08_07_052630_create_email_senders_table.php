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
        Schema::create('email_senders', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Basic
            |--------------------------------------------------------------------------
            */

            $table->string('name');

            $table->string('email')->unique();

            // gmail, outlook, smtp, zoho, ses, mailgun...
            $table->string('provider');

            /*
            |--------------------------------------------------------------------------
            | Limits
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger('daily_limit')->default(200);

            $table->unsignedInteger('hourly_limit')->default(20);

            $table->unsignedInteger('sent_today')->default(0);

            $table->unsignedInteger('sent_this_hour')->default(0);

            /*
            |--------------------------------------------------------------------------
            | Misc
            |--------------------------------------------------------------------------
            */

            $table->longText('signature')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamp('last_sent_at')->nullable();

            $table->timestamp('last_sync_at')->nullable();

            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_senders');
    }
};