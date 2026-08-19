<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InboxMessage extends Model
{
    protected $fillable = [
        'email_sender_id',
        'business_id',
        'campaign_lead_id',
        'organization_id',
        'message_id',
        'in_reply_to',
        'thread_id',
        'folder',
        'from_email',
        'from_name',
        'to_email',
        'to_name',
        'subject',
        'body_html',
        'body_text',
        'snippet',
        'is_read',
        'is_starred',
        'received_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'is_starred' => 'boolean',
        'received_at' => 'datetime',
    ];

    public function emailSender(): BelongsTo
    {
        return $this->belongsTo(EmailSender::class, 'email_sender_id');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function campaignLead(): BelongsTo
    {
        return $this->belongsTo(CampaignLead::class, 'campaign_lead_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function scopeInbox($query)
    {
        return $query->where('folder', 'inbox');
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeStarred($query)
    {
        return $query->where('is_starred', true);
    }
}
