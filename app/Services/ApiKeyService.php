<?php

namespace App\Services;

use App\Models\ApiKey;
use App\Models\Company;
use Illuminate\Support\Str;

class ApiKeyService
{
    /**
     * Generate a new API key
     * Format: blh_ + 60 random alphanumeric characters
     * Total length: 64 characters
     */
    public static function generate(): string
    {
        return 'blh_' . Str::random(60);
    }

    /**
     * Hash an API key using SHA-256
     */
    public static function hash(string $key): string
    {
        return hash('sha256', $key);
    }

    /**
     * Get the prefix of an API key for display purposes
     * Returns first 16 characters (e.g., "blh_AYpbX24Ypo32...")
     */
    public static function getPrefix(string $key): string
    {
        return substr($key, 0, 16) . '...';
    }

    /**
     * Create a new API key for a company
     *
     * @param Company $company
     * @param string $name Descriptive name for the API key
     * @return array Returns ['api_key' => ApiKey model, 'plain_key' => unhashed key]
     */
    public static function createForCompany(Company $company, string $name): array
    {
        $plainKey = self::generate();
        $hashedKey = self::hash($plainKey);
        $prefix = self::getPrefix($plainKey);

        $apiKey = ApiKey::create([
            'company_id' => $company->id,
            'name' => $name,
            'key_hash' => $hashedKey,
            'key_prefix' => $prefix,
            'is_active' => true,
        ]);

        return [
            'api_key' => $apiKey,
            'plain_key' => $plainKey,
        ];
    }

    /**
     * Validate an API key and return the associated company
     *
     * @param string $plainKey The unhashed API key from the request
     * @return Company|null
     */
    public static function validateAndGetCompany(string $plainKey): ?Company
    {
        $hashedKey = self::hash($plainKey);

        $apiKey = ApiKey::where('key_hash', $hashedKey)
            ->where('is_active', true)
            ->first();

        if (!$apiKey) {
            return null;
        }

        // Update last_used_at timestamp
        $apiKey->markAsUsed();

        // Return the company if it's active
        $company = $apiKey->company;

        if (!$company || !$company->is_active) {
            return null;
        }

        return $company;
    }

    /**
     * Revoke (delete) an API key
     */
    public static function revoke(ApiKey $apiKey): bool
    {
        return $apiKey->delete();
    }

    /**
     * Toggle API key active status
     */
    public static function toggle(ApiKey $apiKey): ApiKey
    {
        $apiKey->update(['is_active' => !$apiKey->is_active]);
        return $apiKey->fresh();
    }
}
