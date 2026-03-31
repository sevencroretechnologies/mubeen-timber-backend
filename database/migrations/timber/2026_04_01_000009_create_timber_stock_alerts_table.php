<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timber_stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wood_type_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('stock_ledger_id');
            $table->decimal('current_quantity', 12, 3);
            $table->decimal('threshold', 12, 3);
            $table->enum('alert_type', ['low_stock', 'out_of_stock']);
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('org_id');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['is_resolved', 'company_id']);
            $table->index('alert_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timber_stock_alerts');
    }
};
