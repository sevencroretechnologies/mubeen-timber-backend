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
        Schema::create('company_notices', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->date('publish_date');
            $table->date('expire_date')->nullable();
            $table->boolean('is_company_wide')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('author_id')->nullable();
            $table->timestamps();

            $table->foreign('author_id')->references('id')->on('users')->nullOnDelete();
        });

        // Pivot table for targeted announcements
        Schema::create('company_notice_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_notice_id')->constrained('company_notices')->cascadeOnDelete();
            $table->foreignId('staff_member_id')->constrained('staff_members')->cascadeOnDelete();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['company_notice_id', 'staff_member_id'], 'notice_recipient_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_notice_recipients');
        Schema::dropIfExists('company_notices');
    }
};
