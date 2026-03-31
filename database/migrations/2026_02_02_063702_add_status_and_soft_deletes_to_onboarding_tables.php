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
        Schema::table('onboarding_templates', function (Blueprint $table) {
            $table->string('status')->default('active')->after('days_to_complete');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
};
