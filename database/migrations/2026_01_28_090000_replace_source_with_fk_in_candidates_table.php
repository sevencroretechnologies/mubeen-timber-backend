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
        Schema::table('candidates', function (Blueprint $table) {
            // Drop the old source enum column if it exists
            if (Schema::hasColumn('candidates', 'source')) {
                $table->dropColumn('source');
            }

            // Add the new foreign key column
            if (!Schema::hasColumn('candidates', 'candidate_source_id')) {
                $table->foreignId('candidate_source_id')
                    ->nullable()
                    ->constrained('candidate_sources')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            // Drop the foreign key and column
            if (Schema::hasColumn('candidates', 'candidate_source_id')) {
                $table->dropForeign(['candidate_source_id']);
                $table->dropColumn('candidate_source_id');
            }

            // Restore the old source column
            if (!Schema::hasColumn('candidates', 'source')) {
                $table->enum('source', ['website', 'referral', 'job_portal', 'social_media', 'other'])->default('website');
            }
        });
    }
};
