<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignSequenceStep extends Model
{
    protected $fillable = [
        'email_campaign_id',
        'step_number',
        'email_template_id',
        'delay_days',
        'delay_hours',
        'condition',
    ];

    protected $casts = [
        'step_number' => 'integer',
        'delay_days' => 'integer',
        'delay_hours' => 'integer',
    ];

    public function campaign()
    {
        return $this->belongsTo(EmailCampaign::class, 'email_campaign_id');
    }

    public function template()
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }

    public function leadSteps()
    {
        return $this->hasMany(CampaignLeadStep::class, 'campaign_sequence_step_id');
    }
}
