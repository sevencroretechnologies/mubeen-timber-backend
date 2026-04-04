<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timber_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_ledger_id');
            $table->unsignedBigInteger('wood_type_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->enum('movement_type', ['in', 'out', 'adjustment', 'return']);
            $table->decimal('quantity', 12, 3);
            $table->string('unit', 20)->nullable();
            $table->enum('reference_type', ['purchase_order', 'job_card', 'material_requisition', 'manual', 'estimation_collection']);
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->decimal('total_cost', 12, 2)->nullable();
            $table->decimal('before_quantity', 12, 3);
            $table->decimal('after_quantity', 12, 3);
            $table->text('notes')->nullable();
            $table->date('movement_date');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('org_id');
            $table->unsignedBigInteger('created_by');
            $table->timestamp('created_at')->useCurrent();

            $table->index('movement_type');
            $table->index(['reference_type', 'reference_id']);
            $table->index('movement_date');
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timber_stock_movements');
    }
};
