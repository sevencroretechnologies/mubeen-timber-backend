<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates the estimation_products table for storing product-level estimations
     * with CFT calculations and cost tracking.
     */
    public function up(): void
    {
        Schema::create('estimation_products', function (Blueprint $table) {
            $table->id();

            // Organization & Company (nullable)
            $table->foreignId('org_id')
                  ->nullable()
                  ->constrained('organizations')
                  ->nullOnDelete();

            $table->foreignId('company_id')
                  ->nullable()
                  ->constrained('companies')
                  ->nullOnDelete();

            // Estimation reference
            $table->foreignId('estimation_id')
                  ->nullable()
                  ->constrained('estimations')
                  ->nullOnDelete();

            // Product reference (nullable for inline products)
            $table->foreignId('product_id')
                  ->nullable()
                  ->constrained('products')
                  ->nullOnDelete();

            // Customer reference
            $table->foreignId('customer_id')
                  ->constrained('customers')
                  ->cascadeOnDelete();

            // Project reference
            $table->foreignId('project_id')
                  ->constrained('projects')
                  ->cascadeOnDelete();

            // Dimensions
            $table->decimal('length', 10, 2)->nullable()->default(0);
            $table->decimal('breadth', 10, 2)->nullable()->default(0);
            $table->decimal('height', 10, 2)->nullable()->default(0);
            $table->decimal('thickness', 10, 2)->nullable()->default(0);

            // CFT Calculation Type
            // 1 = length * breadth * height / 1728
            // 2 = length * breadth * thickness / 144
            // 3 = length * breadth * height
            // 4 = custom
            // 5 = manual
            $table->string('cft_calculation_type')->default('1');

            // Quantity & Costs
            $table->integer('quantity')->default(1);
            $table->decimal('cft', 10, 2)->nullable()->default(0);
            $table->decimal('cost_per_cft', 10, 2)->nullable()->default(0);
            $table->decimal('total_amount', 10, 2)->nullable()->default(0);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estimation_products');
    }
};
