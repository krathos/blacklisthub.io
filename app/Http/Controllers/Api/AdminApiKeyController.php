<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Company;
use App\Services\ApiKeyService;
use Illuminate\Http\Request;

/**
 * @group Admin - API Keys Management
 *
 * Admin endpoints for managing API keys across all companies.
 */
class AdminApiKeyController extends Controller
{
    /**
     * List company API keys
     *
     * Get all API keys for a specific company.
     *
     * @authenticated
     *
     * @urlParam companyId integer required The company ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "status": 200,
     *   "message": "API keys retrieved successfully",
     *   "data": {
     *     "company": {
     *       "id": 1,
     *       "name": "Test Company",
     *       "email": "test@company.com"
     *     },
     *     "api_keys": [
     *       {
     *         "id": 1,
     *         "company_id": 1,
     *         "name": "Production Server",
     *         "key_prefix": "blh_AYpbX24Ypo32...",
     *         "is_active": true,
     *         "last_used_at": "2025-12-06T18:45:00.000000Z",
     *         "created_at": "2025-12-06T18:00:00.000000Z",
     *         "updated_at": "2025-12-06T18:45:00.000000Z"
     *       }
     *     ]
     *   }
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "status": 404,
     *   "message": "Company not found",
     *   "data": {}
     * }
     */
    public function index($companyId)
    {
        $company = Company::find($companyId);

        if (!$company) {
            return api_error('Company not found', 404);
        }

        $apiKeys = $company->apiKeys()
            ->orderBy('created_at', 'desc')
            ->get();

        return api_success([
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'email' => $company->email,
            ],
            'api_keys' => $apiKeys,
        ], 'API keys retrieved successfully');
    }

    /**
     * Create API key for company
     *
     * Create a new API key for a specific company (admin only).
     *
     * @authenticated
     *
     * @urlParam companyId integer required The company ID. Example: 1
     *
     * @bodyParam name string required Descriptive name for the API key. Example: Admin Created Key
     *
     * @response 201 {
     *   "success": true,
     *   "status": 201,
     *   "message": "API key created successfully",
     *   "data": {
     *     "api_key": {
     *       "id": 2,
     *       "company_id": 1,
     *       "name": "Admin Created Key",
     *       "key_prefix": "blh_ZxY8wV7uT6s...",
     *       "is_active": true,
     *       "last_used_at": null,
     *       "created_at": "2025-12-06T19:00:00.000000Z",
     *       "updated_at": "2025-12-06T19:00:00.000000Z"
     *     },
     *     "plain_key": "blh_ZxY8wV7uT6s5rQ4pO3nM2lK1jI0hG9fE8dC7bA6ZyXwVuTsRqPoNmLkJiH",
     *     "warning": "Store this API key securely. It will not be shown again."
     *   }
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "status": 404,
     *   "message": "Company not found",
     *   "data": {}
     * }
     */
    public function store(Request $request, $companyId)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $company = Company::find($companyId);

        if (!$company) {
            return api_error('Company not found', 404);
        }

        $result = ApiKeyService::createForCompany($company, $request->name);

        return api_success([
            'api_key' => $result['api_key'],
            'plain_key' => $result['plain_key'],
            'warning' => 'Store this API key securely. It will not be shown again.',
        ], 'API key created successfully', 201);
    }

    /**
     * Update API key
     *
     * Update any API key's name or active status (admin only).
     *
     * @authenticated
     *
     * @urlParam id integer required The API key ID. Example: 1
     *
     * @bodyParam name string optional New name for the API key. Example: Updated by Admin
     * @bodyParam is_active boolean optional Active status. Example: false
     *
     * @response 200 {
     *   "success": true,
     *   "status": 200,
     *   "message": "API key updated successfully",
     *   "data": {
     *     "id": 1,
     *     "company_id": 1,
     *     "name": "Updated by Admin",
     *     "key_prefix": "blh_AYpbX24Ypo32...",
     *     "is_active": false,
     *     "last_used_at": "2025-12-06T18:45:00.000000Z",
     *     "created_at": "2025-12-06T18:00:00.000000Z",
     *     "updated_at": "2025-12-06T19:30:00.000000Z"
     *   }
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "status": 404,
     *   "message": "API key not found",
     *   "data": {}
     * }
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        $apiKey = ApiKey::find($id);

        if (!$apiKey) {
            return api_error('API key not found', 404);
        }

        $apiKey->update($request->only(['name', 'is_active']));

        return api_success($apiKey->fresh(), 'API key updated successfully');
    }

    /**
     * Delete API key
     *
     * Permanently delete any API key (admin only).
     *
     * @authenticated
     *
     * @urlParam id integer required The API key ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "status": 200,
     *   "message": "API key deleted successfully",
     *   "data": null
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "status": 404,
     *   "message": "API key not found",
     *   "data": {}
     * }
     */
    public function destroy($id)
    {
        $apiKey = ApiKey::find($id);

        if (!$apiKey) {
            return api_error('API key not found', 404);
        }

        $apiKey->delete();

        return api_success(null, 'API key deleted successfully');
    }

    /**
     * List all API keys
     *
     * Get all API keys across all companies (admin dashboard).
     *
     * @authenticated
     *
     * @response 200 {
     *   "success": true,
     *   "status": 200,
     *   "message": "All API keys retrieved successfully",
     *   "data": {
     *     "current_page": 1,
     *     "data": [
     *       {
     *         "id": 1,
     *         "company_id": 1,
     *         "name": "Production Server",
     *         "key_prefix": "blh_AYpbX24Ypo32...",
     *         "is_active": true,
     *         "last_used_at": "2025-12-06T18:45:00.000000Z",
     *         "created_at": "2025-12-06T18:00:00.000000Z",
     *         "updated_at": "2025-12-06T18:45:00.000000Z",
     *         "company": {
     *           "id": 1,
     *           "name": "Test Company",
     *           "email": "test@company.com"
     *         }
     *       }
     *     ],
     *     "per_page": 50,
     *     "total": 15
     *   }
     * }
     */
    public function all()
    {
        $apiKeys = ApiKey::with('company:id,name,email')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return api_success($apiKeys, 'All API keys retrieved successfully');
    }
}
