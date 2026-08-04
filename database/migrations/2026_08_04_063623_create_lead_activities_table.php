<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_activities', function (Blueprint $table) {

            $table->id();

            $table->foreignId('business_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('activity_type', [
                'assigned',
                'call',
                'status_changed',
                'comment',
                'followup',
                'converted',
            ]);

            $table->enum('status', [
                'new',
                'interested',
                'call_later',
                'not_interested',
                'didnt_pick',
                'not_reachable',
                'wrong_number',
                'converted',
            ])->nullable();

            $table->text('comment')->nullable();

            $table->timestamp('followup_date')->nullable();

            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index('business_id');
            $table->index('user_id');
            $table->index('activity_type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_activities');
    }
};