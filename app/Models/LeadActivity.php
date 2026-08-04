<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadActivity extends Model
{
    protected $fillable = [
        'business_id',
        'user_id',
        'activity_type',
        'status',
        'comment',
        'followup_date',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'followup_date' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}