<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_template_versions', function (Blueprint $table) {

            $table->id();

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
            | Version
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger('version');

            /*
            |--------------------------------------------------------------------------
            | Email Content
            |--------------------------------------------------------------------------
            */

            $table->string('subject');

            $table->longText('html');

            $table->longText('plain_text')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Version Information
            |--------------------------------------------------------------------------
            */

            $table->text('changelog')->nullable();

            $table->boolean('is_published')->default(false);

            $table->timestamp('published_at')->nullable();

            $table->foreignId('published_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Ownership
            |--------------------------------------------------------------------------
            */

            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique([
                'email_template_id',
                'version',
            ]);

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_template_versions');
    }
};