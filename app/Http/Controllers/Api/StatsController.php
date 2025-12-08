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
        $company = $request->company;
        $query = BlacklistedClient::query();

        // Default: Filter by company's country (jurisdictional filtering)
        $countryCode = $request->input('country_code', $company->country_code);
        $query->where('country_code', $countryCode);

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $totalClients = $query->count();
        $totalReports = $query->sum('reports_count');

        $topReportedClients = BlacklistedClient::with('category')
            ->where('country_code', $countryCode)
            ->when($request->has('category_id'), function($q) use ($request) {
                $q->where('category_id', $request->category_id);
            })
            ->orderBy('reports_count', 'desc')
            ->limit(10)
            ->get(['id', 'name', 'email', 'category_id', 'reports_count', 'trust_score', 'risk_level']);

        $clientsByCategory = Category::withCount(['blacklistedClients' => function($q) use ($countryCode) {
                $q->where('country_code', $countryCode);
            }])
            ->having('blacklisted_clients_count', '>', 0)
            ->orderBy('blacklisted_clients_count', 'desc')
            ->get(['id', 'name', 'blacklisted_clients_count']);

        // Risk level distribution
        $riskDistribution = BlacklistedClient::where('country_code', $countryCode)
            ->selectRaw('risk_level, COUNT(*) as count')
            ->groupBy('risk_level')
            ->get()
            ->pluck('count', 'risk_level');

        // Total debt in USD (converted from all currencies)
        $totalDebtUSD = 0;
        $debtReports = \App\Models\BlacklistReport::whereHas('blacklistedClient', function($q) use ($countryCode) {
                $q->where('country_code', $countryCode);
            })
            ->whereNotNull('debt_amount')
            ->get();

        foreach ($debtReports as $report) {
            $totalDebtUSD += \App\Services\CurrencyService::convertToUSD(
                $report->debt_amount,
                $report->currency ?? 'USD'
            );
        }

        // Get country info
        $countryInfo = collect(\App\Services\CurrencyService::getSupportedCountries())
            ->firstWhere('code', $countryCode);

        $totalCompanies = Company::where('is_active', true)
            ->where('country_code', $countryCode)
            ->count();

        $companyStats = null;
        if ($request->has('company')) {
            $companyStats = [
                'total_reports' => $company->blacklistReports()->count(),
                'unique_clients_reported' => $company->blacklistReports()
                    ->distinct('blacklisted_client_id')
                    ->count('blacklisted_client_id'),
            ];
        }

        return api_success([
            'country' => [
                'code' => $countryCode,
                'name' => $countryInfo['name'] ?? $countryCode,
                'currency' => $countryInfo['currency'] ?? 'USD',
            ],
            'total_blacklisted_clients' => $totalClients,
            'total_reports' => $totalReports,
            'total_debt_usd' => round($totalDebtUSD, 2),
            'total_active_companies' => $totalCompanies,
            'risk_distribution' => $riskDistribution,
            'top_reported_clients' => $topReportedClients,
            'clients_by_category' => $clientsByCategory,
            'company_stats' => $companyStats,
        ], 'Statistics retrieved successfully', 200);
    }
}