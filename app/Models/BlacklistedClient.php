<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlacklistedClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'email',
        'phone',
        'ip_address',
        'rfc_tax_id',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'reports_count',
        'trust_score',
        'risk_level',
        'risk_factors',
        'total_debt',
    ];

    protected $casts = [
        'reports_count' => 'integer',
        'trust_score' => 'integer',
        'risk_factors' => 'array',
        'total_debt' => 'decimal:2',
    ];

    protected $appends = ['risk_badge'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function blacklistReports()
    {
        return $this->hasMany(BlacklistReport::class);
    }

    public function phoneNumbers()
    {
        return $this->hasMany(PhoneNumber::class);
    }

    public function getRiskBadgeAttribute()
    {
        return match($this->risk_level) {
            'CRITICAL' => 'CRITICAL',
            'HIGH' => 'HIGH',
            'MEDIUM' => 'MEDIUM',
            'LOW' => 'LOW',
            default => 'UNKNOWN'
        };
    }

    public function getUniqueCompaniesCountAttribute()
    {
        return $this->blacklistReports()
            ->distinct('company_id')
            ->count('company_id');
    }
}