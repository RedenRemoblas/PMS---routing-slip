<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_leave_types_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeaveTypesTable extends Migration
{
    public function up()
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('leave_name');
            $table->decimal('accrual_rate', 4, 2)->nullable(); // Null for event-based leaves
            $table->integer('expiration_days')->nullable();
            $table->string('fixed_expiry', 5)->nullable(); // Fixed expiration date (e.g., '12-31')
            $table->enum('frequency', ['monthly', 'yearly', 'event_based'])->default('monthly'); // Frequency of accrual
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('leave_types');
    }
}
