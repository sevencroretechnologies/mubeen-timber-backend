<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sales_task_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_task_id')->nullable()->constrained('sales_tasks')->onDelete('cascade');
            $table->date('date');
            $table->time('time');
            $table->text('description');
            $table->enum('status', ['Open', 'In Progress', 'Closed']);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sales_task_details');
    }
};
