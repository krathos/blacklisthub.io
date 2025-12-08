<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Company;
use App\Services\ApiKeyService;
use Symfony\Component\HttpFoundation\Response;

class CompanyAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $company = null;

        // Try to authenticate with Bearer Token first
        $bearerToken = $request->bearerToken();
        if ($bearerToken) {
            $company = Company::where('api_token', hash('sha256', $bearerToken))->first();
        }

        // If no Bearer Token or invalid, try X-API-Key header
        if (!$company) {
            $apiKey = $request->header('X-API-Key');
            if ($apiKey) {
                $company = ApiKeyService::validateAndGetCompany($apiKey);
            }
        }

        // If no authentication method provided
        if (!$bearerToken && !$request->header('X-API-Key')) {
            return api_error('Authentication required. Provide either Bearer Token or X-API-Key header', 401);
        }

        // If authentication failed
        if (!$company) {
            return api_error('Invalid credentials', 401);
        }

        // Check if company is active
        if (!$company->is_active) {
            return api_error('Company not activated', 403);
        }

        // Attach company to request
        $request->merge(['company' => $company]);

        return $next($request);
    }
}