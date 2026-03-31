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
        Schema::table('prospects', function (Blueprint $table) {
            $table->string('company_name')->nullable()->unique(false)->change();
            // Since it was unique before, we might want to keep it unique but nullable.
            // However, multiple nulls might conflict depending on the DB.
            // In most modern DBs (MySQL, Postgres), multiple NULLs are allowed in a UNIQUE index.
            // But let's be safe and just make it nullable first.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->string('company_name')->nullable(false)->change();
        });
    }
};
