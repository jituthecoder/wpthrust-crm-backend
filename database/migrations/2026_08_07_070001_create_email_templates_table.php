<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Basic Information
            |--------------------------------------------------------------------------
            */

            $table->string('name');

            $table->enum('template_type', [
                'cold_email',
                'follow_up',
                'manual',
                'transactional',
            ])->default('cold_email');

            $table->string('category')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            $table->enum('status', [
                'draft',
                'published',
                'archived',
            ])->default('draft');

            /*
            |--------------------------------------------------------------------------
            | Current Version
            |--------------------------------------------------------------------------
            |
            | We are NOT creating a foreign key here to avoid
            | circular dependency. We'll just store the ID.
            |
            */

            $table->unsignedBigInteger('current_version_id')->nullable();

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

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};