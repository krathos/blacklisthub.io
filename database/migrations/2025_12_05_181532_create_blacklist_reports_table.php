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
        Schema::create('blacklist_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blacklisted_client_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->decimal('debt_amount', 10, 2)->nullable();
            $table->date('incident_date')->nullable();
            $table->string('fraud_type')->nullable();
            $table->text('additional_info')->nullable();
            $table->timestamps();
            
            //$table->unique(['blacklisted_client_id', 'company_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blacklist_reports');
    }
};
