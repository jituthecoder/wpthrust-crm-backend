<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Users in this organization
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Businesses in this organization
     */
    public function businesses()
    {
        return $this->hasMany(Business::class);
    }

    /**
     * Email senders in this organization
     */
    public function emailSenders()
    {
        return $this->hasMany(EmailSender::class);
    }

    /**
     * Email templates in this organization
     */
    public function emailTemplates()
    {
        return $this->hasMany(EmailTemplate::class);
    }

    /**
     * Email campaigns in this organization
     */
    public function emailCampaigns()
    {
        return $this->hasMany(EmailCampaign::class);
    }
}
