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

            // Aggregated values from items
            $table->decimal('total_cft', 12, 2)->default(0);
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
