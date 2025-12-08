<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlacklistedClient;
use App\Models\Company;
use App\Models\Category;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    /**
     * Get Statistics
     * 
     * Get general statistics about blacklisted clients and your company's activity.
     * 
     * @authenticated
     * 
     * @queryParam category_id integer Filter statistics by category. Example: 1
     * 
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "status": 200,
     *   "message": "Statistics retrieved successfully",
     *   "data": {
     *     "total_blacklisted_clients": 150,
     *     "total_reports": 200,
     *     "total_active_companies": 25,
     *     "top_reported_clients": [],
     *     "clients_by_category": [],
     *     "company_stats": {
     *       "total_reports": 10,
     *       "unique_clients_reported": 8
     *     }
     *   }
     * }
     */
    public function index(Request $request)
    {
        $query = BlacklistedClient::query();

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $totalClients = $query->count();
        $totalReports = $query->sum('reports_count');

        $topReportedClients = BlacklistedClient::with('category')
            ->when($request->has('category_id'), function($q) use ($request) {
                $q->where('category_id', $request->category_id);
            })
            ->orderBy('reports_count', 'desc')
            ->limit(10)
            ->get(['id', 'name', 'email', 'category_id', 'reports_count']);

        $clientsByCategory = Category::withCount('blacklistedClients')
            ->having('blacklisted_clients_count', '>', 0)
            ->orderBy('blacklisted_clients_count', 'desc')
            ->get(['id', 'name', 'blacklisted_clients_count']);

        $totalCompanies = Company::where('is_active', true)->count();

        $companyStats = null;
        if ($request->has('company')) {
            $company = $request->company;
            $companyStats = [
                'total_reports' => $company->blacklistReports()->count(),
                'unique_clients_reported' => $company->blacklistReports()
                    ->distinct('blacklisted_client_id')
                    ->count('blacklisted_client_id'),
            ];
        }

        return api_success([
            'total_blacklisted_clients' => $totalClients,
            'total_reports' => $totalReports,
            'total_active_companies' => $totalCompanies,
            'top_reported_clients' => $topReportedClients,
            'clients_by_category' => $clientsByCategory,
            'company_stats' => $companyStats,
        ], 'Statistics retrieved successfully', 200);
    }
}