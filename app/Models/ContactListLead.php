<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactListLead extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_list_id',
        'business_id',
        'business_name',
        'email',
        'website',
        'phone',
        'category',
        'country',
        'mobile_pagespeed',
        'notes',
        'custom_fields',
    ];

    protected $casts = [
        'custom_fields' => 'array',
        'mobile_pagespeed' => 'integer',
    ];

    public function getBusinessNameAttribute($value)
    {
        return $value ?? $this->business?->business_name;
    }

    public function getEmailAttribute($value)
    {
        return $value ?? $this->business?->email;
    }

    public function getWebsiteAttribute($value)
    {
        return $value ?? $this->business?->website;
    }

    public function getPhoneAttribute($value)
    {
        return $value ?? $this->business?->phone;
    }

    public function getCategoryAttribute($value)
    {
        return $value ?? $this->business?->category;
    }

    public function getCountryAttribute($value)
    {
        return $value ?? $this->business?->country;
    }

    public function getMobilePagespeedAttribute($value)
    {
        return $value ?? $this->business?->audit?->mobile_pagespeed;
    }

    public function contactList()
    {
        return $this->belongsTo(ContactList::class, 'contact_list_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }
}
