<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {

            $table->id();

            // Business Information
            $table->string('business_name');
            $table->string('category')->nullable();

            $table->string('phone')->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->text('website')->nullable();

            $table->longText('address')->nullable();

            $table->string('city')->nullable()->index();
            $table->string('state')->nullable()->index();

            $table->string('zip_code')->nullable();
            $table->string('country')->nullable();

            /*
             |--------------------------------------------------------------------------
             | CRM
             |--------------------------------------------------------------------------
             */

            $table->foreignId('assigned_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->enum('lead_status', [
                'new',
                'interested',
                'call_later',
                'not_interested',
                'didnt_pick',
                'not_reachable',
                'wrong_number',
                'converted',
            ])->default('new')->index();

            $table->unsignedTinyInteger('lead_priority')->default(1);

            $table->unsignedSmallInteger('call_attempts')->default(0);

            $table->timestamp('last_called_at')->nullable();

            $table->timestamp('next_followup_at')->nullable()->index();

            $table->boolean('is_called')->default(false);

            $table->boolean('is_archived')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};