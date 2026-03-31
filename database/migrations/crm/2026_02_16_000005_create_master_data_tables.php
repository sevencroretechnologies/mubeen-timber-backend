<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Customer Groups
        Schema::create('customer_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Payment Terms
        Schema::create('payment_terms', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->integer('days')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 3. Price Lists
        Schema::create('price_lists', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('currency', 3);
            $table->unique(['name', 'currency']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_lists');
        Schema::dropIfExists('payment_terms');
        Schema::dropIfExists('customer_groups');
    }
};
