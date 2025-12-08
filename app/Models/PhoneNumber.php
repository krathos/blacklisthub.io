<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhoneNumber extends Model
{
    use HasFactory;

    protected $fillable = [
        'blacklisted_client_id',
        'phone',
        'reported_by_company_id',
    ];

    public function blacklistedClient()
    {
        return $this->belongsTo(BlacklistedClient::class);
    }

    public function reportedByCompany()
    {
        return $this->belongsTo(Company::class, 'reported_by_company_id');
    }
}