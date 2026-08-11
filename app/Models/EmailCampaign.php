<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailCampaign extends Model
{
    protected $fillable = [

        /*
        |--------------------------------------------------------------------------
        | Basic
        |--------------------------------------------------------------------------
        */

        'name',

        'description',

        /*
        |--------------------------------------------------------------------------
        | Template
        |--------------------------------------------------------------------------
        */

        'email_template_id',

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        'status',

        'scheduled_at',

        'started_at',

        'completed_at',

        /*
        |--------------------------------------------------------------------------
        | Statistics
        |--------------------------------------------------------------------------
        */

        'total_leads',

        'sent_count',

        'failed_count',

        'opened_count',

        'clicked_count',

        'replied_count',

        'bounced_count',

        /*
        |--------------------------------------------------------------------------
        | Ownership
        |--------------------------------------------------------------------------
        */

        'created_by',

        'organization_id',

    ];

    protected $casts = [

        'scheduled_at' => 'datetime',

        'started_at' => 'datetime',

        'completed_at' => 'datetime',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Organization
     */
    public function organization()
    {
        return $this->belongsTo(
            Organization::class
        );
    }

    /**
     * Template
     */
    public function template()
    {
        return $this->belongsTo(
            EmailTemplate::class,
            'email_template_id'
        );
    }

    /**
     * Creator
     */
    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    /**
     * Campaign Senders
     */
    public function senders()
    {
        return $this->hasMany(
            CampaignSender::class
        );
    }

    /**
     * Campaign Leads
     */
    public function leads()
    {
        return $this->hasMany(
            CampaignLead::class
        );
    }

    /**
     * Campaign Emails
     */
    public function emails()
    {
        return $this->hasMany(
            CampaignEmail::class
        );
    }
}