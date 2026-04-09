<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates the estimation_products_item table for storing individual
     * line items within an estimation product, with dimension, CFT, and cost tracking.
     */
    public function up(): void
    {
        Schema::create('estimation_products_item', function (Blueprint $table) {
            $table->id();

            // Item name / description
            $table->string('name')->nullable();

            // Organization & Company
            $table->foreignId('org_id')
                  ->nullable()
                  ->constrained('organizations')
                  ->nullOnDelete();

            $table->foreignId('company_id')
                  ->nullable()
                  ->constrained('companies')
                  ->nullOnDelete();

            // Parent estimation product reference
            $table->foreignId('estimation_product_id')
                  ->constrained('estimation_products')
                  ->cascadeOnDelete();

            // Estimation reference (denormalized for easy querying)
            $table->foreignId('estimation_id')
                  ->nullable()
                  ->constrained('estimations')
                  ->nullOnDelete();

            // Product reference (denormalized from parent)
            $table->foreignId('product_id')
                  ->nullable()
                  ->constrained('products')
                  ->nullOnDelete();

            // Dimensions
            $table->decimal('length', 10, 2)->nullable()->default(0);
            $table->decimal('breadth', 10, 2)->nullable()->default(0);
            $table->decimal('height', 10, 2)->nullable()->default(0);
            $table->decimal('thickness', 10, 2)->nullable()->default(0);

            // Unit type / CFT calculation type
            // 1 = (L × B × H) / 144  (inches)
            // 2 = L × B × H           (feet)
            // 3 = (L × B × T) / 12    (thickness in inches)
            // 4 = L × B × T           (thickness in feet)
            // 5 = manual
            $table->string('unit_type')->default('1');

            // Quantity, Rate & Calculated fields
            $table->integer('quantity')->default(1);
            $table->decimal('rate', 10, 2)->nullable()->default(0);        // cost per CFT
            $table->decimal('item_cft', 10, 2)->nullable()->default(0);    // calculated CFT per unit
            $table->decimal('total_amount', 12, 2)->nullable()->default(0); // item_cft × rate × quantity

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estimation_products_item');
    }
};
