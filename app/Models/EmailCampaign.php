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

        'auto_sync_enabled',

        'auto_sync_criteria',

    ];

    protected $casts = [

        'scheduled_at' => 'datetime',

        'started_at' => 'datetime',

        'completed_at' => 'datetime',

        'auto_sync_enabled' => 'boolean',

        'auto_sync_criteria' => 'array',

    ];

    protected $appends = [
        'open_rate',
        'click_rate',
    ];

    /**
     * Calculated Open Rate Percentage
     */
    public function getOpenRateAttribute(): float
    {
        $sent = (int) $this->sent_count;
        if ($sent <= 0) {
            $sent = $this->leads()->whereIn('status', ['sent', 'opened', 'clicked'])->count();
        }
        if ($sent <= 0) {
            return 0.0;
        }
        $opened = $this->leads()->whereNotNull('opened_at')->count();
        if ($opened <= 0 && (int) $this->opened_count > 0) {
            $opened = (int) $this->opened_count;
        }
        return round(($opened / $sent) * 100, 1);
    }

    /**
     * Calculated Click Rate Percentage
     */
    public function getClickRateAttribute(): float
    {
        $sent = (int) $this->sent_count;
        if ($sent <= 0) {
            $sent = $this->leads()->whereIn('status', ['sent', 'opened', 'clicked'])->count();
        }
        if ($sent <= 0) {
            return 0.0;
        }
        $clicked = $this->leads()->whereNotNull('clicked_at')->count();
        if ($clicked <= 0 && (int) $this->clicked_count > 0) {
            $clicked = (int) $this->clicked_count;
        }
        return round(($clicked / $sent) * 100, 1);
    }

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
     * Sequence Steps
     */
    public function sequenceSteps()
    {
        return $this->hasMany(
            CampaignSequenceStep::class
        )->orderBy('step_number', 'asc');
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