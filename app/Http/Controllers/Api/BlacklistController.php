<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlacklistedClient;
use App\Models\BlacklistReport;
use App\Models\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BlacklistController extends Controller
{
    public function trustAnalysis($id)
    {
        $client = BlacklistedClient::with([
            'category',
            'blacklistReports.company',
            'blacklistReports.fraudType',
            'phoneNumbers.reportedByCompany'
        ])->findOrFail($id);

        $trustScoreService = new \App\Services\TrustScoreService();
        $analysis = $trustScoreService->calculateTrustScore($client);

        return api_success([
            'client' => $client,
            'trust_analysis' => $analysis
        ], 'Trust analysis completed', 200);
    }

    /**
     * Report Client
     * 
     * Report a single client to the blacklist. If the client already exists, your company will be added to their report list.
     * 
     * @authenticated
     * 
     * @bodyParam category_id integer required The business category ID. Example: 1
     * @bodyParam name string required Client full name. Example: Fernando García
     * @bodyParam email string required Client email address. Example: fernando@gmail.com
     * @bodyParam phone string required Client phone number. Example: 3331234567
     * @bodyParam ip_address string Client IP address. Example: 192.168.1.100
     * @bodyParam rfc_tax_id string Client RFC/Tax ID. Example: GACF850101ABC
     * @bodyParam address string Client address. Example: Av. Principal 123
     * @bodyParam city string Client city. Example: Guadalajara
     * @bodyParam state string Client state/province. Example: Jalisco
     * @bodyParam country string Client country. Example: Mexico
     * @bodyParam postal_code string Client postal code. Example: 44100
     * @bodyParam debt_amount number Amount owed (if applicable). Example: 5000.00
     * @bodyParam incident_date date Date of the incident. Example: 2024-11-15
     * @bodyParam fraud_type_id integer Fraud type ID (see GET /v1/fraud-types). Example: 1
     * @bodyParam additional_info string Additional notes or comments. Example: No pagó 3 envíos
     * 
     * @response 201 scenario="Success" {
     *   "success": true,
     *   "status": 201,
     *   "message": "Client reported successfully",
     *   "data": {
     *     "client": {
     *       "id": 1,
     *       "category_id": 1,
     *       "name": "Fernando García",
     *       "email": "fernando@gmail.com",
     *       "phone": "3331234567",
     *       "reports_count": 1
     *     }
     *   }
     * }
     * 
     * @response 409 scenario="Already Reported" {
     *   "success": false,
     *   "status": 409,
     *   "message": "Your company has already reported this client",
     *   "data": []
     * }
     */
    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:50',
            'ip_address' => 'nullable|string|max:45',
            'rfc_tax_id' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'debt_amount' => 'nullable|numeric|min:0',
            'incident_date' => 'nullable|date',
            'fraud_type_id' => 'nullable|exists:fraud_types,id',
            'additional_info' => 'nullable|string',
        ]);

        $company = $request->company;

        DB::beginTransaction();
        try {
            $client = BlacklistedClient::where('email', $request->email)
                ->orWhere('phone', $request->phone)
                ->first();

            if ($client) {
                $client->increment('reports_count');
            } else {
                $client = BlacklistedClient::create([
                    'category_id' => $request->category_id,
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'ip_address' => $request->ip_address,
                    'rfc_tax_id' => $request->rfc_tax_id,
                    'address' => $request->address,
                    'city' => $request->city,
                    'state' => $request->state,
                    'country' => $request->country,
                    'postal_code' => $request->postal_code,
                    'reports_count' => 1,
                ]);
            }

            BlacklistReport::create([
                'blacklisted_client_id' => $client->id,
                'company_id' => $company->id,
                'debt_amount' => $request->debt_amount,
                'incident_date' => $request->incident_date,
                'fraud_type_id' => $request->fraud_type_id,
                'additional_info' => $request->additional_info,
            ]);

            $phoneExists = PhoneNumber::where('blacklisted_client_id', $client->id)
                ->where('phone', $request->phone)
                ->exists();

            if (!$phoneExists) {
                PhoneNumber::create([
                    'blacklisted_client_id' => $client->id,
                    'phone' => $request->phone,
                    'reported_by_company_id' => $company->id,
                ]);
            }

            $trustScoreService = new \App\Services\TrustScoreService();
            $trustScoreService->updateClientScore($client);

            DB::commit();

            return api_success([
                'client' => $client->fresh()->load(['category', 'blacklistReports.company', 'blacklistReports.fraudType', 'phoneNumbers'])
            ], 'Client reported successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return api_error('Error reporting client', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Bulk Report Clients
     * 
     * Report multiple clients in a single request.
     * 
     * @authenticated
     * 
     * @bodyParam clients array required Array of clients to report.
     * @bodyParam clients[].category_id integer required The business category ID. Example: 1
     * @bodyParam clients[].name string required Client full name. Example: Juan Pérez
     * @bodyParam clients[].email string required Client email address. Example: juan@hotmail.com
     * @bodyParam clients[].phone string required Client phone number. Example: 3339876543
     * @bodyParam clients[].debt_amount number Amount owed. Example: 2500.00
     * @bodyParam clients[].incident_date date Date of incident. Example: 2024-10-20
     * @bodyParam clients[].fraud_type_id integer Fraud type ID. Example: 1
     * @bodyParam clients[].additional_info string Additional notes. Example: No pagó 5 paquetes
     * 
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "status": 200,
     *   "message": "Bulk operation completed",
     *   "data": {
     *     "success": [
     *       {"id": 1, "name": "Juan Pérez", "email": "juan@hotmail.com"},
     *       {"id": 2, "name": "María López", "email": "maria@yahoo.com"}
     *     ],
     *     "errors": []
     *   }
     * }
     */
    public function bulkStore(Request $request)
    {
        $request->validate([
            'clients' => 'required|array|min:1',
            'clients.*.category_id' => 'required|exists:categories,id',
            'clients.*.name' => 'required|string|max:255',
            'clients.*.email' => 'required|email|max:255',
            'clients.*.phone' => 'required|string|max:50',
            'clients.*.ip_address' => 'nullable|string|max:45',
            'clients.*.rfc_tax_id' => 'nullable|string|max:50',
            'clients.*.address' => 'nullable|string',
            'clients.*.city' => 'nullable|string|max:100',
            'clients.*.state' => 'nullable|string|max:100',
            'clients.*.country' => 'nullable|string|max:100',
            'clients.*.postal_code' => 'nullable|string|max:20',
            'clients.*.debt_amount' => 'nullable|numeric|min:0',
            'clients.*.incident_date' => 'nullable|date',
            'clients.*.fraud_type_id' => 'nullable|exists:fraud_types,id',
            'clients.*.additional_info' => 'nullable|string',
        ]);

        $company = $request->company;
        $results = ['success' => [], 'errors' => []];

        foreach ($request->clients as $clientData) {
            DB::beginTransaction();
            try {
                $client = BlacklistedClient::where('email', $clientData['email'])
                    ->orWhere('phone', $clientData['phone'])
                    ->first();

                if ($client) {
                    $client->increment('reports_count');
                } else {
                    $client = BlacklistedClient::create([
                        'category_id' => $clientData['category_id'],
                        'name' => $clientData['name'],
                        'email' => $clientData['email'],
                        'phone' => $clientData['phone'],
                        'ip_address' => $clientData['ip_address'] ?? null,
                        'rfc_tax_id' => $clientData['rfc_tax_id'] ?? null,
                        'address' => $clientData['address'] ?? null,
                        'city' => $clientData['city'] ?? null,
                        'state' => $clientData['state'] ?? null,
                        'country' => $clientData['country'] ?? null,
                        'postal_code' => $clientData['postal_code'] ?? null,
                        'reports_count' => 1,
                    ]);
                }

                BlacklistReport::create([
                    'blacklisted_client_id' => $client->id,
                    'company_id' => $company->id,
                    'debt_amount' => $clientData['debt_amount'] ?? null,
                    'incident_date' => $clientData['incident_date'] ?? null,
                    'fraud_type_id' => $clientData['fraud_type_id'] ?? null,
                    'additional_info' => $clientData['additional_info'] ?? null,
                ]);

                $phoneExists = PhoneNumber::where('blacklisted_client_id', $client->id)
                    ->where('phone', $clientData['phone'])
                    ->exists();

                if (!$phoneExists) {
                    PhoneNumber::create([
                        'blacklisted_client_id' => $client->id,
                        'phone' => $clientData['phone'],
                        'reported_by_company_id' => $company->id,
                    ]);
                }

                $trustScoreService = new \App\Services\TrustScoreService();
                $trustScoreService->updateClientScore($client);

                DB::commit();
                $results['success'][] = $client->only(['id', 'name', 'email']);
            } catch (\Exception $e) {
                DB::rollBack();
                $results['errors'][] = [
                    'email' => $clientData['email'] ?? 'unknown',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return api_success($results, 'Bulk operation completed', 200);
    }

    /**
     * List Blacklisted Clients
     * 
     * Get paginated list of all blacklisted clients. Results are ordered by number of reports (most reported first).
     * 
     * @authenticated
     * 
     * @queryParam per_page integer Number of results per page. Example: 15
     * @queryParam category_id integer Filter by category ID. Example: 1
     * @queryParam page integer Page number. Example: 1
     * 
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "status": 200,
     *   "message": "Clients retrieved successfully",
     *   "data": {
     *     "current_page": 1,
     *     "data": [
     *       {
     *         "id": 1,
     *         "name": "Fernando García",
     *         "email": "fernando@gmail.com",
     *         "phone": "3331234567",
     *         "reports_count": 3,
     *         "category": {"id": 1, "name": "Shipping & Logistics"}
     *       }
     *     ],
     *     "per_page": 15,
     *     "total": 50
     *   }
     * }
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $query = BlacklistedClient::with(['category', 'blacklistReports.company', 'blacklistReports.fraudType']);

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $clients = $query->orderBy('reports_count', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return api_success($clients, 'Clients retrieved successfully', 200);
    }

    /**
     * Search Blacklisted Clients
     * 
     * Search for clients using multiple criteria. All parameters are optional and can be combined.
     * 
     * @authenticated
     * 
     * @queryParam email string Search by email (partial match). Example: fernando
     * @queryParam phone string Search by phone (partial match, includes additional phones). Example: 333
     * @queryParam name string Search by name (partial match). Example: García
     * @queryParam rfc_tax_id string Search by RFC/Tax ID (partial match). Example: GACF
     * @queryParam category_id integer Filter by category. Example: 1
     * @queryParam fraud_type_id integer Filter by fraud type. Example: 1
     * @queryParam per_page integer Results per page. Example: 15
     * 
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "status": 200,
     *   "message": "Search completed successfully",
     *   "data": {
     *     "current_page": 1,
     *     "data": [
     *       {
     *         "id": 1,
     *         "name": "Fernando García",
     *         "email": "fernando@gmail.com",
     *         "phone": "3331234567",
     *         "reports_count": 2
     *       }
     *     ]
     *   }
     * }
     */
    public function search(Request $request)
    {
        $query = BlacklistedClient::with(['category', 'blacklistReports.company', 'blacklistReports.fraudType', 'phoneNumbers']);

        if ($request->has('email')) {
            $query->where('email', 'like', '%' . $request->email . '%');
        }

        if ($request->has('phone')) {
            $phone = $request->phone;
            $query->where(function($q) use ($phone) {
                $q->where('phone', 'like', '%' . $phone . '%')
                  ->orWhereHas('phoneNumbers', function($q2) use ($phone) {
                      $q2->where('phone', 'like', '%' . $phone . '%');
                  });
            });
        }

        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->has('rfc_tax_id')) {
            $query->where('rfc_tax_id', 'like', '%' . $request->rfc_tax_id . '%');
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('fraud_type_id')) {
            $query->whereHas('blacklistReports', function($q) use ($request) {
                $q->where('fraud_type_id', $request->fraud_type_id);
            });
        }

        $perPage = $request->input('per_page', 15);
        $clients = $query->orderBy('reports_count', 'desc')->paginate($perPage);

        return api_success($clients, 'Search completed successfully', 200);
    }

    /**
     * Get Client Details
     * 
     * Get detailed information about a specific blacklisted client, including all companies that reported them.
     * 
     * @authenticated
     * 
     * @urlParam id integer required The client ID. Example: 1
     * 
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "status": 200,
     *   "message": "Client retrieved successfully",
     *   "data": {
     *     "client": {
     *       "id": 1,
     *       "name": "Fernando García",
     *       "email": "fernando@gmail.com",
     *       "reports_count": 2,
     *       "blacklist_reports": [
     *         {
     *           "company": {"name": "Estafeta Express"},
     *           "fraud_type": {"name": "Non Payment"},
     *           "debt_amount": "5000.00",
     *           "additional_info": "No pagó 3 envíos"
     *         }
     *       ]
     *     }
     *   }
     * }
     */
    public function show($id)
    {
        $client = BlacklistedClient::with([
            'category',
            'blacklistReports.company',
            'blacklistReports.fraudType',
            'phoneNumbers.reportedByCompany'
        ])->findOrFail($id);

        return api_success([
            'client' => $client
        ], 'Client retrieved successfully', 200);
    }

    /**
     * Update Client
     * 
     * Update blacklisted client information. Only companies that reported this client can update it.
     * 
     * @authenticated
     * 
     * @urlParam id integer required The client ID. Example: 1
     * @bodyParam name string Client full name. Example: Fernando García López
     * @bodyParam email string Client email. Example: fernando.new@gmail.com
     * @bodyParam phone string Client phone. Example: 3331234567
     * @bodyParam category_id integer Category ID. Example: 1
     * 
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "status": 200,
     *   "message": "Client updated successfully",
     *   "data": {
     *     "client": {
     *       "id": 1,
     *       "name": "Fernando García López",
     *       "email": "fernando.new@gmail.com"
     *     }
     *   }
     * }
     * 
     * @response 403 scenario="Unauthorized" {
     *   "success": false,
     *   "status": 403,
     *   "message": "Unauthorized to update this client",
     *   "data": []
     * }
     */
    public function update(Request $request, $id)
    {
        $client = BlacklistedClient::findOrFail($id);
        $company = $request->company;
        $admin = $request->admin ?? null;

        $report = BlacklistReport::where('blacklisted_client_id', $client->id)
            ->where('company_id', $company->id)
            ->first();

        if (!$report && !$admin) {
            return api_error('Unauthorized to update this client', 403);
        }

        $request->validate([
            'category_id' => 'sometimes|exists:categories,id',
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255',
            'phone' => 'sometimes|string|max:50',
            'ip_address' => 'nullable|string|max:45',
            'rfc_tax_id' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
        ]);

        $client->update($request->only([
            'category_id', 'name', 'email', 'phone', 'ip_address',
            'rfc_tax_id', 'address', 'city', 'state', 'country', 'postal_code'
        ]));

        return api_success([
            'client' => $client->load(['category', 'blacklistReports.company', 'blacklistReports.fraudType'])
        ], 'Client updated successfully', 200);
    }

    public function destroy(Request $request, $id)
    {
        $client = BlacklistedClient::findOrFail($id);
        $client->delete();

        return api_success([], 'Client deleted successfully', 200);
    }
}