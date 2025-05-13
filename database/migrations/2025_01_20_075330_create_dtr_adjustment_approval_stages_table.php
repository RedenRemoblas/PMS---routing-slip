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
        Schema::create('dtr_adjustment_approval_stages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id'); // Approver's employee ID
            $table->string('status')->default('pending'); // Possible values: pending, approved, rejected
            $table->integer('sequence'); // Approval sequence
            $table->text('remarks')->nullable(); // Optional remarks by the approver
            $table->unsignedBigInteger('dtr_adjustment_request_id'); // Reference to the DTR adjustment request

            $table->timestamps();

            // Foreign key constraints
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('dtr_adjustment_request_id')->references('id')->on('dtr_adjustment_requests')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dtr_adjustment_approval_stages');
    }
};
