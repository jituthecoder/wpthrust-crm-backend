<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnsubscribedEmail extends Model
{
    protected $fillable = [
        'organization_id',
        'campaign_id',
        'email',
        'campaign_lead_id',
        'unsubscribed_at',
    ];

    protected $casts = [
        'unsubscribed_at' => 'datetime',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function campaign()
    {
        return $this->belongsTo(EmailCampaign::class, 'campaign_id');
    }

    public function campaignLead()
    {
        return $this->belongsTo(CampaignLead::class);
    }
}
