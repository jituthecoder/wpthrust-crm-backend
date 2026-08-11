<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Create Organizations Table
        |--------------------------------------------------------------------------
        */
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | 2. Create Default Organization & Backfill Existing Data
        |--------------------------------------------------------------------------
        */
        $now = now();
        $defaultOrgId = DB::table('organizations')->insertGetId([
            'name' => 'Default Organization',
            'slug' => 'default-organization',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        /*
        |--------------------------------------------------------------------------
        | 3. Add organization_id to Users Table
        |--------------------------------------------------------------------------
        */
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('organization_id')
                ->nullable()
                ->after('id')
                ->constrained('organizations')
                ->cascadeOnDelete();
        });
        DB::table('users')->whereNull('organization_id')->update(['organization_id' => $defaultOrgId]);

        /*
        |--------------------------------------------------------------------------
        | 4. Add organization_id to Businesses Table
        |--------------------------------------------------------------------------
        */
        Schema::table('businesses', function (Blueprint $table) {
            $table->foreignId('organization_id')
                ->nullable()
                ->after('id')
                ->constrained('organizations')
                ->cascadeOnDelete();
        });
        DB::table('businesses')->whereNull('organization_id')->update(['organization_id' => $defaultOrgId]);

        /*
        |--------------------------------------------------------------------------
        | 5. Add organization_id to Email Senders Table
        |--------------------------------------------------------------------------
        */
        Schema::table('email_senders', function (Blueprint $table) {
            $table->foreignId('organization_id')
                ->nullable()
                ->after('id')
                ->constrained('organizations')
                ->cascadeOnDelete();
        });
        DB::table('email_senders')->whereNull('organization_id')->update(['organization_id' => $defaultOrgId]);

        /*
        |--------------------------------------------------------------------------
        | 6. Add organization_id to Email Templates Table
        |--------------------------------------------------------------------------
        */
        Schema::table('email_templates', function (Blueprint $table) {
            $table->foreignId('organization_id')
                ->nullable()
                ->after('id')
                ->constrained('organizations')
                ->cascadeOnDelete();
        });
        DB::table('email_templates')->whereNull('organization_id')->update(['organization_id' => $defaultOrgId]);

        /*
        |--------------------------------------------------------------------------
        | 7. Add organization_id to Email Campaigns Table
        |--------------------------------------------------------------------------
        */
        Schema::table('email_campaigns', function (Blueprint $table) {
            $table->foreignId('organization_id')
                ->nullable()
                ->after('id')
                ->constrained('organizations')
                ->cascadeOnDelete();
        });
        DB::table('email_campaigns')->whereNull('organization_id')->update(['organization_id' => $defaultOrgId]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_campaigns', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropColumn('organization_id');
        });

        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropColumn('organization_id');
        });

        Schema::table('email_senders', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropColumn('organization_id');
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropColumn('organization_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropColumn('organization_id');
        });

        Schema::dropIfExists('organizations');
    }
};
