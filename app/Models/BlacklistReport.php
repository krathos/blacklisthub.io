<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlacklistReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'blacklisted_client_id',
        'company_id',
        'debt_amount',
        'incident_date',
        'fraud_type_id',
        'additional_info',
    ];

    protected $casts = [
        'debt_amount' => 'decimal:2',
        'incident_date' => 'date',
    ];

    public function blacklistedClient()
    {
        return $this->belongsTo(BlacklistedClient::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function fraudType()
    {
        return $this->belongsTo(FraudType::class);
    }
}