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
        Schema::create('sales_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_source_id')->constrained('task_sources');
            $table->foreignId('task_type_id')->constrained('task_types');
            $table->foreignId('sales_assign_id')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_tasks');
    }
};
