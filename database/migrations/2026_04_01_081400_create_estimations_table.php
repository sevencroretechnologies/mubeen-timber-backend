<?php

use App\Enums\EstimationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estimations', function (Blueprint $table) {
            $table->id();

            // Organization & Company
            $table->foreignId('org_id')
                  ->nullable()
                  ->constrained('organizations')
                  ->nullOnDelete();

            $table->foreignId('company_id')
                  ->nullable()
                  ->constrained('companies')
                  ->nullOnDelete();

            // Core Relations
            $table->foreignId('customer_id')
                  ->constrained('customers')
                  ->cascadeOnDelete();

           

            $table->foreignId('project_id')
                  ->constrained('projects')
                  ->cascadeOnDelete();

            // Description
            $table->text('description')->nullable();
            $table->text('additional_notes')->nullable();


            // Status
            $table->enum('status', array_column(EstimationStatus::cases(), 'value'))
                  ->default(EstimationStatus::Draft->value);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estimations');
    }
};