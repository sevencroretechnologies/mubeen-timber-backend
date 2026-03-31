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
        Schema::table('job_stages', function (Blueprint $table) {
            // Remove order and color columns if they exist
            if (Schema::hasColumn('job_stages', 'order')) {
                $table->dropColumn('order');
            }
            if (Schema::hasColumn('job_stages', 'color')) {
                $table->dropColumn('color');
            }

            // Add description column if it doesn't exist
            if (!Schema::hasColumn('job_stages', 'description')) {
                $table->text('description')->nullable()->after('title');
            }

            // Add status column if it doesn't exist
            if (!Schema::hasColumn('job_stages', 'status')) {
                $table->string('status')->default('active')->after('description');
            }

            // Add soft deletes column if it doesn't exist
            if (!Schema::hasColumn('job_stages', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_stages', function (Blueprint $table) {
            // Drop soft deletes
            $table->dropSoftDeletes();

            // Drop description and status
            $table->dropColumn(['description', 'status']);

            // Add back order and color columns
            $table->integer('order')->default(0)->after('title');
            $table->string('color')->default('#6366f1')->after('order');
        });
    }
};
