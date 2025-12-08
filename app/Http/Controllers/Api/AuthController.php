<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Register Company
     * 
     * Register a new company account. The account will be inactive until approved by an administrator.
     * 
     * @bodyParam name string required The company name. Example: Estafeta Express
     * @bodyParam email string required The company email. Example: contact@estafeta.com
     * @bodyParam password string required The password (minimum 8 characters). Example: password123
     * 
     * @response 201 scenario="Success" {
     *   "success": true,
     *   "status": 201,
     *   "message": "Company registered successfully. Waiting for admin activation.",
     *   "data": {
     *     "company": {
     *       "id": 1,
     *       "name": "Estafeta Express",
     *       "email": "contact@estafeta.com",
     *       "is_active": false
     *     }
     *   }
     * }
     * 
     * @response 422 scenario="Validation Error" {
     *   "success": false,
     *   "status": 422,
     *   "message": "Validation failed",
     *   "data": {
     *     "errors": {
     *       "email": ["The email has already been taken."]
     *     }
     *   }
     * }
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:companies',
            'password' => 'required|string|min:8',
        ]);

        $company = Company::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => false,
        ]);

        return api_success([
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'email' => $company->email,
                'is_active' => $company->is_active,
            ]
        ], 'Company registered successfully. Waiting for admin activation.', 201);
    }

    /**
     * Company Login
     * 
     * Authenticate a company and receive an access token. The company must be activated by an administrator first.
     * 
     * @bodyParam email string required The company email. Example: contact@estafeta.com
     * @bodyParam password string required The password. Example: password123
     * 
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "status": 200,
     *   "message": "Login successful",
     *   "data": {
     *     "company": {
     *       "id": 1,
     *       "name": "Estafeta Express",
     *       "email": "contact@estafeta.com"
     *     },
     *     "token": "XyZ9aB3cD4eF5gH6iJ7kL8mN9oP0qR1sT2uV3wX4yZ5aB6cD7eF8gH9iJ0kL1mN2"
     *   }
     * }
     * 
     * @response 401 scenario="Invalid Credentials" {
     *   "success": false,
     *   "status": 401,
     *   "message": "Invalid credentials",
     *   "data": []
     * }
     * 
     * @response 403 scenario="Not Activated" {
     *   "success": false,
     *   "status": 403,
     *   "message": "Company not activated yet. Please wait for admin approval.",
     *   "data": []
     * }
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $company = Company::where('email', $request->email)->first();

        if (!$company || !Hash::check($request->password, $company->password)) {
            return api_error('Invalid credentials', 401);
        }

        if (!$company->is_active) {
            return api_error('Company not activated yet. Please wait for admin approval.', 403);
        }

        $token = Str::random(80);
        $company->update(['api_token' => hash('sha256', $token)]);

        return api_success([
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'email' => $company->email,
            ],
            'token' => $token,
        ], 'Login successful', 200);
    }

    /**
     * Company Logout
     * 
     * Logout and invalidate the current access token.
     * 
     * @authenticated
     * 
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "status": 200,
     *   "message": "Logout successful",
     *   "data": []
     * }
     */
    public function logout(Request $request)
    {
        $company = $request->company;
        $company->update(['api_token' => null]);

        return api_success([], 'Logout successful', 200);
    }
}