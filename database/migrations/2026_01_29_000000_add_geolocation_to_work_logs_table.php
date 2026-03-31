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
        Schema::table('work_logs', function (Blueprint $table) {
            // Clock-in geolocation
            $table->decimal('clock_in_latitude', 10, 8)->nullable()->after('clock_in_ip');
            $table->decimal('clock_in_longitude', 11, 8)->nullable()->after('clock_in_latitude');
            $table->decimal('clock_in_accuracy', 10, 2)->nullable()->after('clock_in_longitude');
            
            // Clock-out geolocation
            $table->decimal('clock_out_latitude', 10, 8)->nullable()->after('clock_out_ip');
            $table->decimal('clock_out_longitude', 11, 8)->nullable()->after('clock_out_latitude');
            $table->decimal('clock_out_accuracy', 10, 2)->nullable()->after('clock_out_longitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_logs', function (Blueprint $table) {
            $table->dropColumn([
                'clock_in_latitude',
                'clock_in_longitude',
                'clock_in_accuracy',
                'clock_out_latitude',
                'clock_out_longitude',
                'clock_out_accuracy',
            ]);
        });
    }
};
