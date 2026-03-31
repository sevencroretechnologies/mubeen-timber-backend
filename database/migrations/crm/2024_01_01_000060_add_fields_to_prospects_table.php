<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prospects', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->unique();
            $table->string('status')->default('New'); // New, Contacted, Qualified, Lost, Converted
            $table->string('source')->nullable();
            $table->string('industry')->nullable();
            $table->string('market_segment')->nullable();
            $table->string('customer_group')->nullable();
            $table->string('territory')->nullable();
            $table->string('no_of_employees')->nullable();
            $table->decimal('annual_revenue', 15, 2)->nullable();
            $table->string('fax')->nullable();
            $table->string('website')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->foreignId('prospect_owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('company')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prospects');
    }
};
