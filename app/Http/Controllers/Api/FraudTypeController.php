<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FraudType;

class FraudTypeController extends Controller
{
    public function index()
    {
        $fraudTypes = FraudType::where('is_active', true)->orderBy('name')->get();

        return api_success($fraudTypes, 'Fraud types retrieved successfully', 200);
    }
}