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
        // Add international fields to companies table
        Schema::table('companies', function (Blueprint $table) {
            $table->string('country_code', 2)->default('MX')->after('email'); // ISO 3166-1 alpha-2
            $table->string('currency', 3)->default('MXN')->after('country_code'); // ISO 4217
            $table->index('country_code');
        });

        // Update blacklisted_clients table for international support
        Schema::table('blacklisted_clients', function (Blueprint $table) {
            // Rename rfc_tax_id to tax_id (more generic)
            $table->renameColumn('rfc_tax_id', 'tax_id');

            // Rename country to country_code and make it more structured
            $table->renameColumn('country', 'country_code');
        });

        // Add currency and update country_code format in blacklisted_clients
        Schema::table('blacklisted_clients', function (Blueprint $table) {
            $table->string('country_code', 2)->change(); // ISO 3166-1 alpha-2
            $table->string('currency', 3)->default('MXN')->after('country_code'); // ISO 4217
            $table->index('country_code');
        });

        // Add currency context to blacklist_reports
        Schema::table('blacklist_reports', function (Blueprint $table) {
            $table->string('currency', 3)->default('MXN')->after('debt_amount'); // ISO 4217
            $table->index('currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert blacklist_reports changes
        Schema::table('blacklist_reports', function (Blueprint $table) {
            $table->dropIndex(['currency']);
            $table->dropColumn('currency');
        });

        // Revert blacklisted_clients changes (step 2)
        Schema::table('blacklisted_clients', function (Blueprint $table) {
            $table->dropIndex(['country_code']);
            $table->dropColumn('currency');
            $table->string('country_code')->nullable()->change();
        });

        // Revert blacklisted_clients changes (step 1)
        Schema::table('blacklisted_clients', function (Blueprint $table) {
            $table->renameColumn('country_code', 'country');
            $table->renameColumn('tax_id', 'rfc_tax_id');
        });

        // Revert companies changes
        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['country_code']);
            $table->dropColumn(['country_code', 'currency']);
        });
    }
};
