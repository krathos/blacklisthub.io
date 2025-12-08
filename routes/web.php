<?php

use Illuminate\Support\Facades\Route;

// Return JSON error for root path
Route::get('/', function () {
    return response()->json([
        'success' => false,
        'status' => 404,
        'message' => 'Endpoint no encontrado.',
        'data' => (object)[]
    ], 404);
});

// Fallback route for any undefined web routes
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'status' => 404,
        'message' => 'Endpoint no encontrado.',
        'data' => (object)[]
    ], 404);
});
