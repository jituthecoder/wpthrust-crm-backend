<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignDeliveryAttempt extends Model
{
    protected $fillable = [
        'organization_id',
        'email_campaign_id',
        'campaign_lead_id',
        'email_sender_id',
        'attempt_number',
        'idempotency_key',
        'status',
        'sent_subject',
        'sent_body_html',
        'provider_message_id',
        'provider_thread_id',
        'failure_reason',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function campaign()
    {
        return $this->belongsTo(EmailCampaign::class, 'email_campaign_id');
    }

    public function lead()
    {
        return $this->belongsTo(CampaignLead::class, 'campaign_lead_id');
    }

    public function sender()
    {
        return $this->belongsTo(EmailSender::class, 'email_sender_id');
    }

    /**
     * Generate a stable, deterministic idempotency key for a logical delivery attempt.
     */
    public static function generateIdempotencyKey(int $orgId, int $campaignId, int $leadId, int $attemptNumber): string
    {
        return hash('sha256', "org:{$orgId}:campaign:{$campaignId}:lead:{$leadId}:attempt:{$attemptNumber}");
    }
}
