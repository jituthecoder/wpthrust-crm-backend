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

    ];

    protected $casts = [

        'is_active' => 'boolean',

        'last_sent_at' => 'datetime',

        'last_sync_at' => 'datetime',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

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