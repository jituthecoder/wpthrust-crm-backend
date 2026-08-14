<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignLeadStep extends Model
{
    protected $fillable = [
        'campaign_lead_id',
        'campaign_sequence_step_id',
        'step_number',
        'status',
        'scheduled_at',
        'sent_at',
    ];

    protected $casts = [
        'step_number' => 'integer',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function campaignLead()
    {
        return $this->belongsTo(CampaignLead::class, 'campaign_lead_id');
    }

    public function sequenceStep()
    {
        return $this->belongsTo(CampaignSequenceStep::class, 'campaign_sequence_step_id');
    }
}
