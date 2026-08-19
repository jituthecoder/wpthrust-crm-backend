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
        'is_bounced',
        'bounced_at',
    ];

    protected function casts(): array
    {
        return [
            'is_called' => 'boolean',
            'is_archived' => 'boolean',
            'is_bounced' => 'boolean',
            'last_called_at' => 'datetime',
            'next_followup_at' => 'datetime',
            'bounced_at' => 'datetime',
        ];
    }

    public function scopeNeverBounced($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('is_bounced')
              ->orWhere('is_bounced', false);
        })->whereNull('bounced_at');
    }

    public function scopeBounced($query)
    {
        return $query->where('is_bounced', true)->orWhereNotNull('bounced_at');
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