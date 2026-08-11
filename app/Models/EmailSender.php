<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailSender extends Model
{
    protected $fillable = [

        /*
        |--------------------------------------------------------------------------
        | Basic
        |--------------------------------------------------------------------------
        */

        'name',

        'display_name',

        'email',

        'provider',

        /*
        |--------------------------------------------------------------------------
        | Limits
        |--------------------------------------------------------------------------
        */

        'daily_limit',

        'hourly_limit',

        'sent_today',

        'sent_this_hour',

        'reserved_today',

        'reserved_this_hour',

        'last_daily_reset_at',

        'last_hourly_reset_at',

        'last_reserved_at',

        /*
        |--------------------------------------------------------------------------
        | Misc
        |--------------------------------------------------------------------------
        */

        'signature',

        'is_active',

        'last_sent_at',

        'last_sync_at',

        'created_by',

        'organization_id',

    ];

    protected $casts = [

        'is_active' => 'boolean',

        'last_sent_at' => 'datetime',

        'last_sync_at' => 'datetime',

        'last_daily_reset_at' => 'datetime',

        'last_hourly_reset_at' => 'datetime',

        'last_reserved_at' => 'datetime',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function organization()
    {
        return $this->belongsTo(
            Organization::class
        );
    }

    public function senderAccount()
    {
        return $this->hasOne(
            EmailSenderAccount::class,
            'email_sender_id'
        );
    }

    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }
}