<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::table('performance_objectives', function (Blueprint $table) {
        $table->foreignId('org_id')->nullable()->after('author_id');
        $table->foreignId('company_id')->nullable()->after('org_id');
    });
}

public function down()
{
    Schema::table('performance_objectives', function (Blueprint $table) {
        $table->dropColumn(['org_id', 'company_id']);
    });
}
};
