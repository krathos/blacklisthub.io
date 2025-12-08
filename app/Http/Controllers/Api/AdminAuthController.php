<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $admin = Admin::where('email', $request->email)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return api_error('Invalid credentials', 401);
        }

        $token = Str::random(80);
        $admin->update(['api_token' => hash('sha256', $token)]);

        return api_success([
            'admin' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
            ],
            'token' => $token,
        ], 'Admin login successful', 200);
    }

    public function logout(Request $request)
    {
        $admin = $request->admin;
        $admin->update(['api_token' => null]);

        return api_success([], 'Admin logout successful', 200);
    }
}