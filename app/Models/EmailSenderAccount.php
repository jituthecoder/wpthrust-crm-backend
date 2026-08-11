<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailSenderAccount extends Model
{
    protected $fillable = [

        'email_sender_id',

        'settings',

    ];

    protected $casts = [

        'settings' => 'array',

    ];

    protected $hidden = [

        'settings',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function emailSender()
    {
        return $this->belongsTo(
            EmailSender::class,
            'email_sender_id'
        );
    }
}