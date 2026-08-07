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
        Schema::create('email_campaigns', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Basic Information
            |--------------------------------------------------------------------------
            */

            $table->string('name');

            $table->text('description')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Template
            |--------------------------------------------------------------------------
            */

            $table->foreignId('email_template_id')
                ->constrained('email_templates')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Campaign Status
            |--------------------------------------------------------------------------
            */

            $table->enum('status', [

                'draft',

                'scheduled',

                'running',

                'paused',

                'completed',

                'cancelled',

            ])->default('draft');

            /*
            |--------------------------------------------------------------------------
            | Scheduling
            |--------------------------------------------------------------------------
            */

            $table->timestamp('scheduled_at')->nullable();

            $table->timestamp('started_at')->nullable();

            $table->timestamp('completed_at')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Statistics
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger('total_leads')->default(0);

            $table->unsignedInteger('sent_count')->default(0);

            $table->unsignedInteger('failed_count')->default(0);

            $table->unsignedInteger('opened_count')->default(0);

            $table->unsignedInteger('clicked_count')->default(0);

            $table->unsignedInteger('replied_count')->default(0);

            $table->unsignedInteger('bounced_count')->default(0);

            /*
            |--------------------------------------------------------------------------
            | Ownership
            |--------------------------------------------------------------------------
            */

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
        Schema::dropIfExists('email_campaigns');
    }
};