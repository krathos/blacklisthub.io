<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;

class CategoryController extends Controller
{
    /**
     * List Categories
     * 
     * Get all available business categories for blacklist reporting.
     * 
     * @authenticated
     * 
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "status": 200,
     *   "message": "Categories retrieved successfully",
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Shipping & Logistics",
     *       "slug": "shipping-logistics",
     *       "description": "Shipping companies, courier services, freight"
     *     },
     *     {
     *       "id": 2,
     *       "name": "E-commerce",
     *       "slug": "e-commerce",
     *       "description": "Online stores, marketplaces, retail"
     *     }
     *   ]
     * }
     */
    public function index()
    {
        $categories = Category::orderBy('name')->get();

        return api_success($categories, 'Categories retrieved successfully', 200);
    }
}