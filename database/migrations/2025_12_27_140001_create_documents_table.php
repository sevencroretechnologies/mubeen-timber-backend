<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            // Using generic bigInteger for company_id if 'companies' table doesn't strictly exist yet, otherwise foreignId can be used.
            $table->unsignedBigInteger('company_id')->nullable()->index(); 
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Uploader
            
            // Refers to 2025_12_16_180000_create_document_types_table.php
            $table->foreignId('document_type_id')->nullable()->constrained('document_types')->nullOnDelete();
            
            // Location reference
            $table->foreignId('document_location_id')->constrained('document_locations');

            // Polymorphic / Enum Owner Logic
            // owner_type stores: 'employee', 'company', 'accountant'
            $table->string('owner_type'); 
            // owner_id stores the specific ID (userdocument_id)
            $table->unsignedBigInteger('owner_id');
            $table->index(['owner_type', 'owner_id']);

            // File Details
            $table->string('document_name');
            $table->text('doc_url'); // Path or Full URL
            $table->unsignedBigInteger('document_size')->default(0); // In Bytes
            $table->string('document_extension', 10)->nullable();
            $table->string('mime_type')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
