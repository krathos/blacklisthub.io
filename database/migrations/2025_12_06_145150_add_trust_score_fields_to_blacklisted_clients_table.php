<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blacklisted_clients', function (Blueprint $table) {
            $table->integer('trust_score')->default(100)->after('reports_count');
            $table->string('risk_level')->default('LOW')->after('trust_score');
            $table->json('risk_factors')->nullable()->after('risk_level');
            $table->decimal('total_debt', 10, 2)->default(0)->after('risk_factors');
        });
    }

    public function down(): void
    {
        Schema::table('blacklisted_clients', function (Blueprint $table) {
            $table->dropColumn(['trust_score', 'risk_level', 'risk_factors', 'total_debt']);
        });
    }
};