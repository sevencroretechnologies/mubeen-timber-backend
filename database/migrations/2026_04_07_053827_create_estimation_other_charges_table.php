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
        Schema::create('estimation_other_charges', function (Blueprint $table) {
            $table->id();
            
            // Foreign Keys
            $table->foreignId('estimation_id')->constrained('estimations')->nullOnDelete();
            $table->foreignId('org_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();

            // Charges and Costs
            $table->decimal('labour_charges', 15, 2)->default(0)->nullable();
            $table->decimal('transport_and_handling', 15, 2)->default(0)->nullable();
            $table->decimal('discount', 15, 2)->default(0)->nullable();
            $table->decimal('approximate_tax', 15, 2)->default(0)->nullable();
            $table->decimal('overall_total_cft', 15, 2)->default(0)->nullable();

            // Other Description
            $table->decimal('other_description_amount', 15, 2)->default(0)->nullable();
            $table->text('other_description')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estimation_other_charges');
    }
};
