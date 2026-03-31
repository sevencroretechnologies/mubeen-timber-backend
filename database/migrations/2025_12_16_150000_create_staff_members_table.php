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
        Schema::create('staff_members', function (Blueprint $table) {
            $table->id();

            // Link to user account
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Personal Information
            $table->string('full_name');
            $table->string('personal_email')->nullable();
            $table->string('mobile_number', 20)->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->text('home_address')->nullable();

            // Passport/Identity
            $table->string('nationality', 100)->nullable();
            $table->string('passport_number', 50)->nullable();
            $table->string('country_code', 3)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('city_name', 100)->nullable();
            $table->string('postal_code', 20)->nullable();

            // Employment Information
            $table->string('staff_code', 50)->unique();
            $table->string('biometric_id', 50)->nullable();
            $table->foreignId('office_location_id')->nullable()->constrained('office_locations')->nullOnDelete();
            $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();
            $table->foreignId('job_title_id')->nullable()->constrained('job_titles')->nullOnDelete();
            $table->date('hire_date')->nullable();

            // Banking Information
            $table->string('bank_account_name')->nullable();
            $table->string('bank_account_number', 50)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_branch')->nullable();

            // Compensation
            $table->enum('compensation_type', ['monthly', 'hourly', 'daily', 'contract'])->default('monthly');
            $table->decimal('base_salary', 12, 2)->default(0);

            // Status
            $table->enum('employment_status', ['active', 'on_leave', 'suspended', 'terminated', 'resigned'])->default('active');

            // Multi-tenancy
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('author_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('author_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_members');
    }
};
