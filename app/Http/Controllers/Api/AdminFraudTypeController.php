<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FraudType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminFraudTypeController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $fraudTypes = FraudType::withCount('blacklistReports')->orderBy('name')->paginate($perPage);

        return api_success($fraudTypes, 'Fraud types retrieved successfully', 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:fraud_types',
            'description' => 'nullable|string',
        ]);

        $fraudType = FraudType::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'is_active' => true,
        ]);

        return api_success([
            'fraud_type' => $fraudType
        ], 'Fraud type created successfully', 201);
    }

    public function update(Request $request, $id)
    {
        $fraudType = FraudType::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:fraud_types,name,' . $id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $fraudType->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'is_active' => $request->is_active ?? $fraudType->is_active,
        ]);

        return api_success([
            'fraud_type' => $fraudType
        ], 'Fraud type updated successfully', 200);
    }

    public function destroy($id)
    {
        $fraudType = FraudType::findOrFail($id);
        $fraudType->delete();

        return api_success([], 'Fraud type deleted successfully', 200);
    }
}