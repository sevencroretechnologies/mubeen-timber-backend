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
        Schema::create('prospect_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospect_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            // Pivot columns from Prospect model definition
            $table->string('lead_name')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile_no')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });

        Schema::create('prospect_opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospect_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opportunity_id')->constrained()->cascadeOnDelete();
            // Pivot columns from Prospect model definition
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('stage')->nullable();
            $table->string('deal_owner')->nullable(); // references user name or id? Model says 'deal_owner'
            $table->integer('probability')->nullable();
            $table->date('expected_closing')->nullable();
            $table->string('currency')->nullable();
            $table->string('contact_person')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prospect_opportunities');
        Schema::dropIfExists('prospect_leads');
    }
};
