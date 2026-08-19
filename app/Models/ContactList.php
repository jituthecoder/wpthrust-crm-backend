<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactList extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'description',
        'total_contacts',
        'created_by',
    ];

    public function listLeads()
    {
        return $this->hasMany(ContactListLead::class, 'contact_list_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
