<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'organization_id',
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function assignedBusinesses()
    {
        return $this->hasMany(Business::class, 'assigned_user_id');
    }

    public function businessComments()
    {
        return $this->hasMany(BusinessComment::class);
    }
    public function leadActivities()
    {
        return $this->hasMany(LeadActivity::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}