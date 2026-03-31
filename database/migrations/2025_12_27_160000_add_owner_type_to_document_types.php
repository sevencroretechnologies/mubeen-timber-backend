<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            // We store the enum ID as an integer (e.g. 1, 2, 3)
            $table->unsignedTinyInteger('owner_type_id')->nullable()->after('id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->dropColumn('owner_type_id');
        });
    }
};
