<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timber_wood_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('org_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->string('category', 100)->nullable();
            $table->decimal('default_rate', 12, 2)->default(0);
            $table->string('unit', 20)->default('CFT');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['org_id', 'company_id']);
            $table->index('code');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timber_wood_types');
    }
};
