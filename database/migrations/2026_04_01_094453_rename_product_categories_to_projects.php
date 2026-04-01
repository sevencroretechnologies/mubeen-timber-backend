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
    // Rename table
    if (Schema::hasTable('product_categories')) {
        Schema::rename('product_categories', 'projects');
    }

    // Rename column safely
    if (Schema::hasTable('products') && Schema::hasColumn('products', 'category_id')) {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);

            // Rename column
            $table->renameColumn('category_id', 'project_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreign('project_id')
                  ->references('id')
                  ->on('projects')
                  ->nullOnDelete(); 
        });
    }
}
};
