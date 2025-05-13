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
        Schema::create('dtrs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->datetime('dtr_timestamp');
            $table->string('log_type', 32);

            $table->string('employee_dtr_no', 32); // No renaming, remains as is
            $table->foreign('employee_dtr_no')->references('employee_no')->on('employees')->onDelete('cascade'); // Link to employees.employee_no

            $table->string('device_serial_no', 50);
            $table->string('verify_mode', 8);
            $table->BigInteger('sequence_no');
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dtrs');
    }
};
