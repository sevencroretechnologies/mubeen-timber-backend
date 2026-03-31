<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timber_warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('code', 50)->unique();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('org_id');
            $table->timestamps();
            $table->softDeletes();

            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timber_warehouses');
    }
};
