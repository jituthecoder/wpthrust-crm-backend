<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = [

        /*
        |--------------------------------------------------------------------------
        | Basic
        |--------------------------------------------------------------------------
        */

        'name',

        'template_type',

        'category',

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        'status',

        /*
        |--------------------------------------------------------------------------
        | Current Version
        |--------------------------------------------------------------------------
        */

        'current_version_id',

        /*
        |--------------------------------------------------------------------------
        | Ownership
        |--------------------------------------------------------------------------
        */

        'created_by',

        'organization_id',

    ];

    protected $casts = [];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Organization
     */
    public function organization()
    {
        return $this->belongsTo(
            Organization::class
        );
    }

    /**
     * All versions
     */
    public function versions()
    {
        return $this->hasMany(
            EmailTemplateVersion::class,
            'email_template_id'
        )->orderBy('version', 'desc');
    }

    /**
     * Current Version
     */
    public function currentVersion()
    {
        return $this->belongsTo(
            EmailTemplateVersion::class,
            'current_version_id'
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

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePublished($query)
    {
        return $query->where(
            'status',
            'published'
        );
    }

    public function scopeDraft($query)
    {
        return $query->where(
            'status',
            'draft'
        );
    }

    public function scopeArchived($query)
    {
        return $query->where(
            'status',
            'archived'
        );
    }
}