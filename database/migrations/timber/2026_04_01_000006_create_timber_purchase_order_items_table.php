<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timber_purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_order_id');
            $table->unsignedBigInteger('wood_type_id');
            $table->decimal('quantity', 12, 3);
            $table->decimal('received_quantity', 12, 3)->default(0.000);
            $table->string('unit', 20);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 12, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('purchase_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timber_purchase_order_items');
    }
};
