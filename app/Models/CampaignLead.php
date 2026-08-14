<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignLead extends Model
{
    protected $fillable = [

        /*
        |--------------------------------------------------------------------------
        | Relationships
        |--------------------------------------------------------------------------
        */

        'email_campaign_id',

        'business_id',

        'email_sender_id',

        'email_template_version_id',

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        'status',

        /*
        |--------------------------------------------------------------------------
        | Retry
        |--------------------------------------------------------------------------
        */

        'retry_count',

        'max_retry',

        /*
        |--------------------------------------------------------------------------
        | Provider
        |--------------------------------------------------------------------------
        */

        'provider_message_id',

        'provider_thread_id',

        /*
        |--------------------------------------------------------------------------
        | Scheduling
        |--------------------------------------------------------------------------
        */

        'scheduled_at',

        'processing_started_at',

        'last_attempt_at',

        'sent_at',

        /*
        |--------------------------------------------------------------------------
        | Failure
        |--------------------------------------------------------------------------
        */

        'failure_reason',

        /*
        |--------------------------------------------------------------------------
        | Tracking
        |--------------------------------------------------------------------------
        */

        'opened_at',

        'clicked_at',

        'replied_at',

        'unsubscribe_token',

        'unsubscribed_at',

    ];

    protected $attributes = [

        'status' => 'pending',

        'retry_count' => 0,

        'max_retry' => 3,

    ];

    protected $casts = [

        'scheduled_at' => 'datetime',

        'processing_started_at' => 'datetime',

        'last_attempt_at' => 'datetime',

        'sent_at' => 'datetime',

        'opened_at' => 'datetime',

        'clicked_at' => 'datetime',

        'replied_at' => 'datetime',

        'unsubscribed_at' => 'datetime',

    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($lead) {
            if (empty($lead->unsubscribe_token)) {
                $lead->unsubscribe_token = \Illuminate\Support\Str::random(32);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Campaign
     */
    public function campaign()
    {
        return $this->belongsTo(
            EmailCampaign::class,
            'email_campaign_id'
        );
    }

    /**
     * Business
     */
    public function business()
    {
        return $this->belongsTo(
            Business::class
        );
    }

    /**
     * Sender
     */
    public function sender()
    {
        return $this->belongsTo(
            EmailSender::class,
            'email_sender_id'
        );
    }

    /**
     * Template Version
     */
    public function templateVersion()
    {
        return $this->belongsTo(
            EmailTemplateVersion::class,
            'email_template_version_id'
        );
    }

    /**
     * Delivery Attempts
     */
    public function deliveryAttempts()
    {
        return $this->hasMany(
            CampaignDeliveryAttempt::class,
            'campaign_lead_id'
        );
    }

    /**
     * Latest Delivery Attempt
     */
    public function latestAttempt()
    {
        return $this->hasOne(
            CampaignDeliveryAttempt::class,
            'campaign_lead_id'
        )->latestOfMany();
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeDue($query, $timestamp = null)
    {
        $time = $timestamp ?? now();
        return $query->where('status', 'pending')
            ->where(function ($q) use ($time) {
                $q->whereNull('scheduled_at')
                  ->orWhere('scheduled_at', '<=', $time);
            });
    }

    public function scopeStaleProcessing($query, int $timeoutMinutes = 10)
    {
        return $query->where('status', 'processing')
            ->where('processing_started_at', '<=', now()->subMinutes($timeoutMinutes));
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function canRetry(): bool
    {
        return $this->status === 'failed' && $this->retry_count < $this->max_retry;
    }
}