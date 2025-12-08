<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('blacklist_reports', function (Blueprint $table) {
            $table->dropColumn('fraud_type');
        });

        Schema::table('blacklist_reports', function (Blueprint $table) {
            $table->foreignId('fraud_type_id')->nullable()->after('incident_date')->constrained()->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blacklist_reports', function (Blueprint $table) {
            $table->dropForeign(['fraud_type_id']);
            $table->dropColumn('fraud_type_id');
        });

        Schema::table('blacklist_reports', function (Blueprint $table) {
            $table->string('fraud_type')->nullable()->after('incident_date');
        });
    }
};
