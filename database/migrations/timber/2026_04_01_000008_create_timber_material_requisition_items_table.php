<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timber_material_requisition_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('requisition_id');
            $table->unsignedBigInteger('wood_type_id');
            $table->decimal('requested_quantity', 12, 3);
            $table->decimal('approved_quantity', 12, 3)->nullable();
            $table->decimal('issued_quantity', 12, 3)->default(0.000);
            $table->decimal('returned_quantity', 12, 3)->default(0.000);
            $table->string('unit', 20);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('requisition_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timber_material_requisition_items');
    }
};
