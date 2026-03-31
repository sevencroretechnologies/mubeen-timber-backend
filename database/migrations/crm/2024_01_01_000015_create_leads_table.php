<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('series')->nullable();
            
            // Personal Information
            $table->string('salutation')->nullable();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('job_title')->nullable();
            $table->string('gender')->nullable();
            
            // References
            $table->foreignId('status_id')->nullable()->constrained('statuses')->nullOnDelete();
            $table->foreignId('source_id')->nullable()->constrained('sources')->nullOnDelete();
            $table->foreignId('request_type_id')->nullable()->constrained('request_types')->nullOnDelete();
            
            // Contact Information
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile_no')->nullable();
            $table->string('website')->nullable();
            $table->string('whatsapp_no')->nullable();

             
            // Location
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            
            // Company Information
            $table->string('company_name')->nullable();
            $table->decimal('annual_revenue', 15, 2)->nullable();
            $table->string('no_of_employees')->nullable();
            $table->foreignId('industry_id')->nullable()->constrained('industry_types')->nullOnDelete();
            
            // Qualification
            $table->string('qualification_status')->nullable();
            $table->foreignId('qualified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('qualified_on')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
