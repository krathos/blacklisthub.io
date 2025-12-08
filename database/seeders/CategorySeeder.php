<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Shipping & Logistics', 'description' => 'Shipping companies, courier services, freight'],
            ['name' => 'E-commerce', 'description' => 'Online stores, marketplaces, retail'],
            ['name' => 'Financial Services', 'description' => 'Banks, lenders, payment processors'],
            ['name' => 'Professional Services', 'description' => 'Consultants, agencies, freelancers'],
            ['name' => 'Real Estate', 'description' => 'Property rentals, sales, management'],
            ['name' => 'Telecommunications', 'description' => 'Phone, internet, cable services'],
            ['name' => 'Hospitality', 'description' => 'Hotels, restaurants, travel'],
            ['name' => 'Healthcare', 'description' => 'Medical services, clinics, pharmacies'],
            ['name' => 'Education', 'description' => 'Schools, courses, training'],
            ['name' => 'Other', 'description' => 'Other business categories'],
        ];

        foreach ($categories as $category) {
            Category::create([
                'name' => $category['name'],
                'slug' => Str::slug($category['name']),
                'description' => $category['description'],
            ]);
        }
    }
}