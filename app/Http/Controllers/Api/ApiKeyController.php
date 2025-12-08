<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Services\ApiKeyService;
use Illuminate\Http\Request;

/**
 * @group API Keys Management
 *
 * APIs for managing company API keys. Companies can create, list, update, and delete their own API keys.
 */
class ApiKeyController extends Controller
{
    /**
     * List API keys
     *
     * Get all API keys for the authenticated company.
     *
     * @authenticated
     *
     * @response 200 {
     *   "success": true,
     *   "status": 200,
     *   "message": "API keys retrieved successfully",
     *   "data": [
     *     {
     *       "id": 1,
     *       "company_id": 1,
     *       "name": "Production Server",
     *       "key_prefix": "blh_AYpbX24Ypo32...",
     *       "is_active": true,
     *       "last_used_at": "2025-12-06T18:45:00.000000Z",
     *       "created_at": "2025-12-06T18:00:00.000000Z",
     *       "updated_at": "2025-12-06T18:45:00.000000Z"
     *     }
     *   ]
     * }
     */
    public function index(Request $request)
    {
        $company = $request->company;

        $apiKeys = $company->apiKeys()
            ->orderBy('created_at', 'desc')
            ->get();

        return api_success($apiKeys, 'API keys retrieved successfully');
    }

    /**
     * Create API key
     *
     * Create a new API key for the authenticated company. The plain key is returned only once.
     *
     * @authenticated
     *
     * @bodyParam name string required Descriptive name for the API key. Example: Production Server
     *
     * @response 201 {
     *   "success": true,
     *   "status": 201,
     *   "message": "API key created successfully",
     *   "data": {
     *     "api_key": {
     *       "id": 1,
     *       "company_id": 1,
     *       "name": "Production Server",
     *       "key_prefix": "blh_AYpbX24Ypo32...",
     *       "is_active": true,
     *       "last_used_at": null,
     *       "created_at": "2025-12-06T18:45:00.000000Z",
     *       "updated_at": "2025-12-06T18:45:00.000000Z"
     *     },
     *     "plain_key": "blh_AYpbX24Ypo32HlIMocKVb1OCPxdvKBtfiSMg304Ffceb40c2a1b2c3",
     *     "warning": "Store this API key securely. It will not be shown again."
     *   }
     * }
     *
     * @response 400 {
     *   "success": false,
     *   "status": 400,
     *   "message": "Maximum number of API keys reached (10)",
     *   "data": {}
     * }
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $company = $request->company;

        // Optional: Limit number of API keys per company
        $maxKeys = 10;
        if ($company->apiKeys()->count() >= $maxKeys) {
            return api_error("Maximum number of API keys reached ($maxKeys)", 400);
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
     * Update an API key's name or active status.
     *
     * @authenticated
     *
     * @urlParam id integer required The API key ID. Example: 1
     *
     * @bodyParam name string optional New name for the API key. Example: Updated Name
     * @bodyParam is_active boolean optional Active status. Example: false
     *
     * @response 200 {
     *   "success": true,
     *   "status": 200,
     *   "message": "API key updated successfully",
     *   "data": {
     *     "id": 1,
     *     "company_id": 1,
     *     "name": "Updated Name",
     *     "key_prefix": "blh_AYpbX24Ypo32...",
     *     "is_active": false,
     *     "last_used_at": "2025-12-06T18:45:00.000000Z",
     *     "created_at": "2025-12-06T18:00:00.000000Z",
     *     "updated_at": "2025-12-06T19:00:00.000000Z"
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

        $company = $request->company;

        $apiKey = ApiKey::where('id', $id)
            ->where('company_id', $company->id)
            ->first();

        if (!$apiKey) {
            return api_error('API key not found', 404);
        }

        $apiKey->update($request->only(['name', 'is_active']));

        return api_success($apiKey->fresh(), 'API key updated successfully');
    }

    /**
     * Delete API key
     *
     * Permanently delete an API key. This action cannot be undone.
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
    public function destroy(Request $request, $id)
    {
        $company = $request->company;

        $apiKey = ApiKey::where('id', $id)
            ->where('company_id', $company->id)
            ->first();

        if (!$apiKey) {
            return api_error('API key not found', 404);
        }

        $apiKey->delete();

        return api_success(null, 'API key deleted successfully');
    }
}
