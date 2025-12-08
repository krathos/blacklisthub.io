<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Company extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'password',
        'country_code',
        'currency',
        'is_active',
        'api_token',
    ];

    protected $hidden = [
        'password',
        'api_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function blacklistReports()
    {
        return $this->hasMany(BlacklistReport::class);
    }

    public function phoneNumbers()
    {
        return $this->hasMany(PhoneNumber::class, 'reported_by_company_id');
    }

    public function apiKeys()
    {
        return $this->hasMany(ApiKey::class);
    }
}