<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates the estimation_products_item table for storing individual
     * product line items within an estimation, with dimension and CFT tracking.
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

            // Estimation reference
            $table->foreignId('estimation_id')
                  ->nullable()
                  ->constrained('estimations')
                  ->nullOnDelete();

            // Product reference
            $table->foreignId('product_id')
                  ->nullable()
                  ->constrained('products')
                  ->nullOnDelete();

            // Customer reference
            $table->foreignId('customer_id')
                  ->nullable()
                  ->constrained('customers')
                  ->nullOnDelete();

            // Project reference
            $table->foreignId('project_id')
                  ->nullable()
                  ->constrained('projects')
                  ->nullOnDelete();

            // Dimensions
            $table->decimal('length', 10, 2)->nullable()->default(0);
            $table->decimal('breadth', 10, 2)->nullable()->default(0);
            $table->decimal('height', 10, 2)->nullable()->default(0);
            $table->decimal('thickness', 10, 2)->nullable()->default(0);

            // Quantity & CFT
            $table->integer('quantity')->default(1);
            $table->decimal('item_cft', 10, 2)->nullable()->default(0);

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
