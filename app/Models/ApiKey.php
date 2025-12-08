<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'key_hash',
        'key_prefix',
        'is_active',
        'last_used_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    protected $hidden = [
        'key_hash', // Never expose the hashed key
    ];

    /**
     * Get the company that owns the API key
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Update the last_used_at timestamp
     */
    public function markAsUsed()
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Scope to get only active API keys
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
