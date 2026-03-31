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
        Schema::table('staff_members', function (Blueprint $table) {
            if (!Schema::hasColumn('staff_members', 'profile_image')) {
                $table->string('profile_image')->nullable()->after('full_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff_members', function (Blueprint $table) {
            if (Schema::hasColumn('staff_members', 'profile_image')) {
                $table->dropColumn('profile_image');
            }
        });
    }
};
