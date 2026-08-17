<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Business extends Model
{
    protected $fillable = [
        'organization_id',
        'business_name',
        'category',
        'phone',
        'email',
        'website',
        'domain',
        'address',
        'city',
        'state',
        'zip_code',
        'country',

        'lead_source',

        'assigned_user_id',
        'lead_status',
        'lead_priority',
        'call_attempts',
        'last_called_at',
        'next_followup_at',
        'is_called',
        'is_archived',
    ];

    protected function casts(): array
    {
        return [
            'is_called' => 'boolean',
            'is_archived' => 'boolean',
            'last_called_at' => 'datetime',
            'next_followup_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($business) {
            try {
                app(\App\Services\Email\Campaign\CampaignAutoSyncService::class)->syncMatchingLeads($business);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Auto-sync error on Business creation #{$business->id}: " . $e->getMessage());
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function audit(): HasOne
    {
        return $this->hasOne(BusinessAudit::class);
    }

   public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)
            ->latest();
    }
    
}