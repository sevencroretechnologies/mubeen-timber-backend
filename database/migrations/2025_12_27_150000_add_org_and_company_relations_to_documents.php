<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Add org_id with foreign key constraint
            if (!Schema::hasColumn('documents', 'org_id')) {
                $table->foreignId('org_id')->nullable()->after('id')
                      ->constrained('organizations')->nullOnDelete();
            }

            // Update company_id to have foreign key constraint
            // Note: company_id already exists as unsignedBigInteger from previous migration
            if (Schema::hasColumn('documents', 'company_id')) {
                // Ensure the column type is correct before adding constraint (it should be)
                $table->foreign('company_id')
                      ->references('id')->on('companies')
                      ->nullOnDelete();
            } else {
                $table->foreignId('company_id')->nullable()->after('org_id')
                      ->constrained('companies')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['org_id']);
            $table->dropColumn('org_id');

            $table->dropForeign(['company_id']);
            // We don't drop company_id column because it might have data/existed before, 
            // but for full rollback of this specific "add" action:
            // If we are strictly reversing 'up', we would remove the constraint.
        });
    }
};
