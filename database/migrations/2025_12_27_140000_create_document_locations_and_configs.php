<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Document Locations (Local, Wasabi, AWS)
        Schema::create('document_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Local, Wasabi, AWS
            $table->string('slug')->unique(); // local, wasabi, aws
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Local Config
        Schema::create('document_local_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('document_locations')->onDelete('cascade');
            $table->string('root_path')->default('storage/documents');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Wasabi Config
        Schema::create('document_wasabi_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('document_locations')->onDelete('cascade');
            $table->string('bucket');
            $table->string('region')->default('us-east-1');
            $table->string('access_key');
            $table->string('secret_key');
            $table->string('endpoint')->nullable(); // Important for Wasabi
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 4. AWS Config
        Schema::create('document_aws_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('document_locations')->onDelete('cascade');
            $table->string('bucket');
            $table->string('region')->default('us-east-1');
            $table->string('access_key');
            $table->string('secret_key');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_aws_configs');
        Schema::dropIfExists('document_wasabi_configs');
        Schema::dropIfExists('document_local_configs');
        Schema::dropIfExists('document_locations');
    }
};
