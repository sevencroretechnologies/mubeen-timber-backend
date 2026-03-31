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
        Schema::create('time_off_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_member_id')->constrained('staff_members')->cascadeOnDelete();
            $table->foreignId('time_off_category_id')->constrained('time_off_categories')->cascadeOnDelete();
            $table->date('request_date');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_days', 5, 1); // Supports half days
            $table->text('reason')->nullable();
            $table->enum('approval_status', ['pending', 'approved', 'declined'])->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->text('approval_remarks')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('author_id')->nullable();
            $table->timestamps();

            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('author_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_off_requests');
    }
};
