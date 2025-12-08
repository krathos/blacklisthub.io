<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\AdminCompanyController;
use App\Http\Controllers\Api\AdminCategoryController;
use App\Http\Controllers\Api\AdminFraudTypeController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\FraudTypeController;
use App\Http\Controllers\Api\BlacklistController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\ApiKeyController;
use App\Http\Controllers\Api\AdminApiKeyController;
use App\Http\Controllers\Api\InternationalController;

// Company Authentication
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('company.auth');
});

// Admin Authentication
Route::prefix('admin')->group(function () {
    Route::post('login', [AdminAuthController::class, 'login']);
    Route::post('logout', [AdminAuthController::class, 'logout'])->middleware('admin.auth');

    // Admin - Company Management
    Route::middleware('admin.auth')->group(function () {
        Route::get('companies', [AdminCompanyController::class, 'index']);
        Route::put('companies/{id}/activate', [AdminCompanyController::class, 'activate']);
        Route::put('companies/{id}/deactivate', [AdminCompanyController::class, 'deactivate']);

        // Admin - Category Management
        Route::get('categories', [AdminCategoryController::class, 'index']);
        Route::post('categories', [AdminCategoryController::class, 'store']);
        Route::put('categories/{id}', [AdminCategoryController::class, 'update']);
        Route::delete('categories/{id}', [AdminCategoryController::class, 'destroy']);

        // Admin - Fraud Type Management
        Route::get('fraud-types', [AdminFraudTypeController::class, 'index']);
        Route::post('fraud-types', [AdminFraudTypeController::class, 'store']);
        Route::put('fraud-types/{id}', [AdminFraudTypeController::class, 'update']);
        Route::delete('fraud-types/{id}', [AdminFraudTypeController::class, 'destroy']);
    });
});

// Categories (Public with token)
Route::middleware('company.auth')->group(function () {
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('fraud-types', [FraudTypeController::class, 'index']);
});

// International Support (Countries & Currencies)
Route::middleware('company.auth')->group(function () {
    Route::get('countries', [InternationalController::class, 'countries']);
    Route::get('countries/{code}', [InternationalController::class, 'countryDetails']);
    Route::get('currencies', [InternationalController::class, 'currencies']);
    Route::get('currency/convert', [InternationalController::class, 'convertCurrency']);
});

// Blacklist Operations
Route::middleware('company.auth')->group(function () {
    Route::post('blacklist', [BlacklistController::class, 'store']);
    Route::post('blacklist/bulk', [BlacklistController::class, 'bulkStore']);
    Route::get('blacklist', [BlacklistController::class, 'index']);
    Route::get('blacklist/search', [BlacklistController::class, 'search']);
    Route::get('blacklist/{id}', [BlacklistController::class, 'show']);
    Route::put('blacklist/{id}', [BlacklistController::class, 'update']);
});

// Delete only for admin
Route::middleware('admin.auth')->group(function () {
    Route::delete('blacklist/{id}', [BlacklistController::class, 'destroy']);
});

// Statistics
Route::middleware('company.auth')->group(function () {
    Route::get('stats', [StatsController::class, 'index']);
    Route::get('trust-analysis/{id}', [BlacklistController::class, 'trustAnalysis']);
});

// API Keys Management (Company)
Route::middleware('company.auth')->prefix('api-keys')->group(function () {
    Route::get('/', [ApiKeyController::class, 'index']);
    Route::post('/', [ApiKeyController::class, 'store']);
    Route::put('{id}', [ApiKeyController::class, 'update']);
    Route::delete('{id}', [ApiKeyController::class, 'destroy']);
});

// API Keys Management (Admin)
Route::middleware('admin.auth')->prefix('admin')->group(function () {
    Route::get('api-keys', [AdminApiKeyController::class, 'all']);
    Route::get('companies/{companyId}/api-keys', [AdminApiKeyController::class, 'index']);
    Route::post('companies/{companyId}/api-keys', [AdminApiKeyController::class, 'store']);
    Route::put('api-keys/{id}', [AdminApiKeyController::class, 'update']);
    Route::delete('api-keys/{id}', [AdminApiKeyController::class, 'destroy']);
});