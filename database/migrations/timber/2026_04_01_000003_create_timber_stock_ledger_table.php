<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timber_stock_ledger', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wood_type_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->decimal('current_quantity', 12, 3)->default(0.000);
            $table->decimal('reserved_quantity', 12, 3)->default(0.000);
            $table->decimal('minimum_threshold', 12, 3)->default(0.000);
            $table->decimal('maximum_threshold', 12, 3)->default(0.000);
            $table->timestamp('last_restocked_at')->nullable();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('org_id');
            $table->timestamps();

            $table->unique(['wood_type_id', 'warehouse_id', 'company_id'], 'unique_wood_warehouse_company');
            $table->index('company_id');
            $table->index('wood_type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timber_stock_ledger');
    }
};
