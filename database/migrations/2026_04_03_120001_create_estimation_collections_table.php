<?php

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
        Schema::create('estimation_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estimation_id')->constrained('estimations')->onDelete('cascade');
            $table->foreignId('wood_type_id')->constrained('timber_wood_types')->onDelete('restrict');
            $table->foreignId('warehouse_id')->constrained('timber_warehouses')->onDelete('restrict');
            $table->decimal('quantity_cft', 10, 3);
            $table->text('notes')->nullable();
            $table->timestamp('collected_at')->useCurrent();
            $table->foreignId('collected_by')->constrained('users')->onDelete('restrict');
              $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estimation_collections');
    }
};
