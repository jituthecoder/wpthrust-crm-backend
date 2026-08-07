<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignSender extends Model
{
    protected $fillable = [

        /*
        |--------------------------------------------------------------------------
        | Relationships
        |--------------------------------------------------------------------------
        */

        'email_campaign_id',

        'email_sender_id',

        /*
        |--------------------------------------------------------------------------
        | Sending Configuration
        |--------------------------------------------------------------------------
        */

        'priority',

        'weight',

        /*
        |--------------------------------------------------------------------------
        | Limits
        |--------------------------------------------------------------------------
        */

        'daily_limit',

        'hourly_limit',

        /*
        |--------------------------------------------------------------------------
        | Statistics
        |--------------------------------------------------------------------------
        */

        'sent_count',

        'failed_count',

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        'is_active',

        'last_sent_at',

    ];

    protected $casts = [

        'is_active' => 'boolean',

        'last_sent_at' => 'datetime',

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
     * Sender
     */
    public function sender()
    {
        return $this->belongsTo(
            EmailSender::class,
            'email_sender_id'
        );
    }
}