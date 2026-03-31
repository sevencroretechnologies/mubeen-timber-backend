<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            // Foreign Keys
            $table->string('customer_type')->nullable();
            $table->foreignId('customer_group_id')->nullable()->constrained('customer_groups')->nullOnDelete();
            $table->foreignId('territory_id')->nullable()->constrained('territories')->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('opportunity_id')->nullable()->constrained('opportunities')->nullOnDelete();
            $table->foreignId('industry_id')->nullable()->constrained('industry_types')->nullOnDelete();
            $table->foreignId('default_price_list_id')->nullable()->constrained('price_lists')->nullOnDelete();
            $table->foreignId('payment_term_id')->nullable()->constrained('payment_terms')->nullOnDelete();

            // Assuming customer_contacts is the table for contacts
            $table->foreignId('customer_contact_id')->nullable()->constrained('customer_contacts')->nullOnDelete();

            // Other Fields
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('tax_id')->nullable();

            $table->string('billing_currency')->nullable();
            $table->text('bank_account_details')->nullable();
            $table->string('print_language')->nullable();
            $table->text('customer_details')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
