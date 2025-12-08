<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;

class AdminCompanyController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $companies = Company::orderBy('created_at', 'desc')->paginate($perPage);

        return api_success($companies, 'Companies retrieved successfully', 200);
    }

    public function activate($id)
    {
        $company = Company::findOrFail($id);
        $company->update(['is_active' => true]);

        return api_success([
            'company' => $company
        ], 'Company activated successfully', 200);
    }

    public function deactivate($id)
    {
        $company = Company::findOrFail($id);
        $company->update(['is_active' => false]);

        return api_success([
            'company' => $company
        ], 'Company deactivated successfully', 200);
    }
}