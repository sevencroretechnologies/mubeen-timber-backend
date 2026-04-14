<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timber_purchase_orders', function (Blueprint $table) {
            $table->foreignId('tax_group_id')->nullable()->after('tax_percentage')->constrained('tax_groups')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('timber_purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['tax_group_id']);
            $table->dropColumn('tax_group_id');
        });
    }
};
