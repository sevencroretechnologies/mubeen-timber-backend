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
        Schema::create('grievances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('filed_by_staff_id')->constrained('staff_members')->cascadeOnDelete();
            $table->unsignedBigInteger('against_staff_id')->nullable();
            $table->unsignedBigInteger('against_division_id')->nullable();
            $table->string('subject');
            $table->date('incident_date');
            $table->text('description');
            $table->enum('status', ['filed', 'investigating', 'resolved', 'dismissed'])->default('filed');
            $table->text('resolution')->nullable();
            $table->date('resolved_date')->nullable();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('author_id')->nullable();
            $table->timestamps();

            $table->foreign('against_staff_id')->references('id')->on('staff_members')->nullOnDelete();
            $table->foreign('against_division_id')->references('id')->on('divisions')->nullOnDelete();
            $table->foreign('author_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grievances');
    }
};
