<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration adds missing foreign key constraints to blacklist_reports table.
     * It checks if constraints exist before attempting to create them (idempotent).
     */
    public function up(): void
    {
        // Check and add foreign key for blacklisted_client_id if it doesn't exist
        $clientFkExists = DB::select("
            SELECT COUNT(*) as count
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
            AND TABLE_NAME = 'blacklist_reports'
            AND CONSTRAINT_NAME = 'blacklist_reports_blacklisted_client_id_foreign'
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ");

        if ($clientFkExists[0]->count == 0) {
            DB::statement('
                ALTER TABLE blacklist_reports
                ADD CONSTRAINT blacklist_reports_blacklisted_client_id_foreign
                FOREIGN KEY (blacklisted_client_id)
                REFERENCES blacklisted_clients(id)
                ON DELETE CASCADE
            ');
        }

        // Check and add foreign key for company_id if it doesn't exist
        $companyFkExists = DB::select("
            SELECT COUNT(*) as count
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
            AND TABLE_NAME = 'blacklist_reports'
            AND CONSTRAINT_NAME = 'blacklist_reports_company_id_foreign'
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ");

        if ($companyFkExists[0]->count == 0) {
            DB::statement('
                ALTER TABLE blacklist_reports
                ADD CONSTRAINT blacklist_reports_company_id_foreign
                FOREIGN KEY (company_id)
                REFERENCES companies(id)
                ON DELETE CASCADE
            ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys if they exist
        DB::statement('
            ALTER TABLE blacklist_reports
            DROP FOREIGN KEY IF EXISTS blacklist_reports_blacklisted_client_id_foreign
        ');

        DB::statement('
            ALTER TABLE blacklist_reports
            DROP FOREIGN KEY IF EXISTS blacklist_reports_company_id_foreign
        ');
    }
};
