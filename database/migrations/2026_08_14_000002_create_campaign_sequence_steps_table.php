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
        if (!Schema::hasTable('campaign_sequence_steps')) {
            Schema::create('campaign_sequence_steps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('email_campaign_id')->constrained('email_campaigns')->onDelete('cascade');
                $table->integer('step_number')->default(1);
                $table->foreignId('email_template_id')->nullable()->constrained('email_templates')->onDelete('set null');
                $table->integer('delay_days')->default(2);
                $table->integer('delay_hours')->default(0);
                $table->enum('condition', ['always', 'if_opened', 'if_not_opened', 'if_clicked', 'if_not_clicked'])->default('always');
                $table->timestamps();

                $table->unique(['email_campaign_id', 'step_number']);
            });
        }

        if (!Schema::hasTable('campaign_lead_steps')) {
            Schema::create('campaign_lead_steps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('campaign_lead_id')->constrained('campaign_leads')->onDelete('cascade');
                $table->foreignId('campaign_sequence_step_id')->constrained('campaign_sequence_steps')->onDelete('cascade');
                $table->integer('step_number');
                $table->enum('status', ['pending', 'scheduled', 'sent', 'skipped', 'failed'])->default('pending');
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();

                $table->unique(['campaign_lead_id', 'step_number']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_lead_steps');
        Schema::dropIfExists('campaign_sequence_steps');
    }
};
