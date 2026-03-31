<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timber_material_requisitions', function (Blueprint $table) {
            $table->id();
            $table->string('requisition_code', 50)->unique();
            $table->unsignedBigInteger('job_card_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('requested_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->date('requisition_date');
            $table->enum('status', ['pending', 'approved', 'issued', 'partial_issued', 'rejected', 'returned'])->default('pending');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('org_id');
            $table->timestamps();
            $table->softDeletes();

            $table->index('job_card_id');
            $table->index('project_id');
            $table->index('status');
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timber_material_requisitions');
    }
};
