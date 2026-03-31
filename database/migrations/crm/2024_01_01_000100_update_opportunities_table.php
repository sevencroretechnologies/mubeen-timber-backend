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
        Schema::table('opportunities', function (Blueprint $table) {
            $table->boolean('with_items')->default(false);
            $table->string('name')->nullable(); // Cache for Customer/Lead Name
            $table->foreignId('territory_id')->nullable()->constrained('territories')->nullOnDelete();
            $table->string('contact_person')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_mobile')->nullable();
            $table->text('to_discuss')->nullable();
            $table->string('next_contact_by')->nullable();
            $table->date('next_contact_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropForeign(['territory_id']);
            $table->dropColumn([
                'with_items',
                'name',
                'territory_id',
                'contact_person',
                'contact_email',
                'contact_mobile',
                'to_discuss',
                'next_contact_by',
                'next_contact_date',
            ]);
        });
    }
};
