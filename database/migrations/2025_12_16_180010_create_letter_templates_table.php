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
        Schema::create('letter_templates', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('template_type', ['joining', 'experience', 'noc', 'offer', 'termination', 'other'])->default('other');
            $table->string('language', 10)->default('en');
            $table->longText('content'); // HTML content with placeholders
            $table->json('placeholders')->nullable(); // Available placeholders
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('author_id')->nullable();
            $table->timestamps();

            $table->foreign('author_id')->references('id')->on('users')->nullOnDelete();
        });

        // Generated letters for staff
        Schema::create('generated_letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_member_id')->constrained('staff_members')->cascadeOnDelete();
            $table->foreignId('letter_template_id')->constrained('letter_templates')->cascadeOnDelete();
            $table->string('reference_number')->unique();
            $table->longText('rendered_content'); // Final HTML after placeholder replacement
            $table->string('pdf_path')->nullable();
            $table->date('issue_date');
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('author_id')->nullable();
            $table->timestamps();

            $table->foreign('author_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generated_letters');
        Schema::dropIfExists('letter_templates');
    }
};
