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

    ];

    protected $casts = [

        'scheduled_at' => 'datetime',

        'processing_started_at' => 'datetime',

        'last_attempt_at' => 'datetime',

        'sent_at' => 'datetime',

        'opened_at' => 'datetime',

        'clicked_at' => 'datetime',

        'replied_at' => 'datetime',

    ];

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
}