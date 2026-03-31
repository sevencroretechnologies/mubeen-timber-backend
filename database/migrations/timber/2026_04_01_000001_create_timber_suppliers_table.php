<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timber_suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('supplier_code', 50)->unique();
            $table->string('name', 255);
            $table->string('contact_person', 255)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 255)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('pincode', 20)->nullable();
            $table->string('gst_number', 50)->nullable();
            $table->string('pan_number', 50)->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account', 50)->nullable();
            $table->string('ifsc_code', 20)->nullable();
            $table->string('payment_terms', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('org_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('company_id');
            $table->index('supplier_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timber_suppliers');
    }
};
