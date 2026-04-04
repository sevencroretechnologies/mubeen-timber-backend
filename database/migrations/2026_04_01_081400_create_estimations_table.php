<?php

use App\Enums\EstimationStatus;
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
        Schema::create('estimations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained('projects')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('estimation_type');
            $table->decimal('length', 10, 2)->nullable();
            $table->decimal('breadth', 10, 2)->nullable();
            $table->decimal('height', 10, 2)->nullable();
            $table->decimal('thickness', 10, 2)->nullable();
            $table->integer('quantity')->nullable();
            $table->decimal('cft', 10, 2)->nullable();
            $table->decimal('cost_per_cft', 10, 2)->nullable();
            $table->decimal('labor_charges', 10, 2)->nullable();
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->enum('status', array_column(EstimationStatus::cases(), 'value'))->default(EstimationStatus::Draft->value);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estimations');
    }
};
