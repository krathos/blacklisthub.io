<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Admin;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return api_error('Token not provided', 401);
        }

        $admin = Admin::where('api_token', hash('sha256', $token))->first();

        if (!$admin) {
            return api_error('Invalid admin token', 401);
        }

        $request->merge(['admin' => $admin]);

        return $next($request);
    }
}