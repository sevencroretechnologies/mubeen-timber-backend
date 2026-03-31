<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->string('naming_series')->nullable();
            
            // Type and Stage - referencing existing tables
            $table->foreignId('opportunity_type_id')->nullable()->constrained('opportunity_types')->nullOnDelete();
            $table->foreignId('opportunity_stage_id')->nullable()->constrained('opportunity_stages')->nullOnDelete();
            
            // Opportunity Details
            $table->enum('opportunity_from', ['lead', 'customer', 'prospect'])->nullable();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('source_id')->nullable()->constrained('sources')->nullOnDelete();
            $table->date('expected_closing')->nullable();
            $table->string('party_name')->nullable();
            $table->foreignId('opportunity_owner')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('probability', 5, 2)->nullable(); // e.g., 75.50%
            $table->foreignId('status_id')->nullable()->constrained('statuses')->nullOnDelete();
            
            // Company Information
            $table->string('company_name')->nullable();
            $table->foreignId('industry_id')->nullable()->constrained('industry_types')->nullOnDelete();
            $table->string('no_of_employees')->nullable();
            
            // Location
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            
            // Financial
            $table->decimal('annual_revenue', 15, 2)->nullable();
            $table->string('market_segment')->nullable();
            $table->string('currency', 10)->nullable()->default('USD');
            $table->decimal('opportunity_amount', 15, 2)->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};
