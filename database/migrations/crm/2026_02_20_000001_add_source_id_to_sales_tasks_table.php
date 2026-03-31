<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add source_id to sales_tasks.
     * This stores the actual lead/prospect/opportunity ID.
     * The task_source_id (1=Lead, 2=Prospect, 3=Opportunity) determines which table source_id refers to.
     * No FK constraint because it's polymorphic (points to different tables).
     */
    public function up(): void
    {
        Schema::table('sales_tasks', function (Blueprint $table) {
            $table->unsignedBigInteger('source_id')->nullable()->after('task_source_id');
            $table->index(['task_source_id', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::table('sales_tasks', function (Blueprint $table) {
            $table->dropIndex(['task_source_id', 'source_id']);
            $table->dropColumn('source_id');
        });
    }
};
