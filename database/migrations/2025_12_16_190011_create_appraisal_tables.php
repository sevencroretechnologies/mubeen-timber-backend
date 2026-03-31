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
        Schema::create('appraisal_cycles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->date('cycle_start');
            $table->date('cycle_end');
            $table->date('review_deadline')->nullable();
            $table->enum('status', ['draft', 'active', 'closed'])->default('draft');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('author_id')->nullable();
            $table->timestamps();

            $table->foreign('author_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('appraisal_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appraisal_cycle_id')->constrained('appraisal_cycles')->cascadeOnDelete();
            $table->foreignId('staff_member_id')->constrained('staff_members')->cascadeOnDelete();
            $table->unsignedBigInteger('reviewer_id')->nullable();
            $table->enum('status', ['pending', 'self_review', 'manager_review', 'completed'])->default('pending');
            $table->text('self_assessment')->nullable();
            $table->text('manager_feedback')->nullable();
            $table->decimal('overall_rating', 3, 2)->nullable();
            $table->text('strengths')->nullable();
            $table->text('improvements')->nullable();
            $table->text('career_goals')->nullable();
            $table->timestamp('self_submitted_at')->nullable();
            $table->timestamp('manager_submitted_at')->nullable();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->timestamps();

            $table->foreign('reviewer_id')->references('id')->on('users')->nullOnDelete();
            $table->unique(['appraisal_cycle_id', 'staff_member_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appraisal_records');
        Schema::dropIfExists('appraisal_cycles');
    }
};
