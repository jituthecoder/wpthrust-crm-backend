<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplateVersion extends Model
{
    protected $fillable = [

        /*
        |--------------------------------------------------------------------------
        | Template
        |--------------------------------------------------------------------------
        */

        'email_template_id',

        /*
        |--------------------------------------------------------------------------
        | Version
        |--------------------------------------------------------------------------
        */

        'version',

        /*
        |--------------------------------------------------------------------------
        | Content
        |--------------------------------------------------------------------------
        */

        'subject',

        'html',

        'plain_text',

        /*
        |--------------------------------------------------------------------------
        | Version Info
        |--------------------------------------------------------------------------
        */

        'changelog',

        'is_published',

        'published_at',

        'published_by',

        /*
        |--------------------------------------------------------------------------
        | Ownership
        |--------------------------------------------------------------------------
        */

        'created_by',

    ];

    protected $casts = [

        'is_published' => 'boolean',

        'published_at' => 'datetime',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Parent Template
     */
    public function template()
    {
        return $this->belongsTo(
            EmailTemplate::class,
            'email_template_id'
        );
    }

    /**
     * Creator
     */
    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    /**
     * Publisher
     */
    public function publisher()
    {
        return $this->belongsTo(
            User::class,
            'published_by'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePublished($query)
    {
        return $query->where(
            'is_published',
            true
        );
    }
}