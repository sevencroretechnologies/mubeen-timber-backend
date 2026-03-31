<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opportunity_products', function (Blueprint $table) {
            $table->decimal('rate', 15, 2)->default(0)->after('quantity');
            $table->decimal('amount', 15, 2)->default(0)->after('rate');
        });
    }

    public function down(): void
    {
        Schema::table('opportunity_products', function (Blueprint $table) {
            $table->dropColumn(['rate', 'amount']);
        });
    }
};
