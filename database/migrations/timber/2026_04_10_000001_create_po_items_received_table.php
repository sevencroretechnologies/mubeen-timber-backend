<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('po_items_received', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->nullable()->constrained('timber_purchase_orders')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('timber_warehouses')->nullOnDelete();
            $table->decimal('received_quantity', 12, 3);
            $table->date('received_date');
            $table->decimal('total_amount', 12, 2)->default(0.00)->nullable();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('org_id');
            $table->timestamps();
            $table->softDeletes();

            $table->index('purchase_order_id');
            $table->index('warehouse_id');
            $table->index('company_id');
            $table->index('received_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('po_items_received');
    }
};
