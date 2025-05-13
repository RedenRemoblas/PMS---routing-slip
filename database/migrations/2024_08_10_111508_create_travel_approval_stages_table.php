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
        Schema::create('travel_approval_stages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');

            $table->string('status')->default('pending'); // Possible values: pending, approved, rejected
            $table->integer('sequence');
            $table->text('remarks')->nullable();  // Optional remarks by the approver
            $table->unsignedBigInteger('travel_order_id'); // Reference to the travel order

            $table->timestamps();

            // Foreign key constraints
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');

            $table->foreign('travel_order_id')->references('id')->on('travel_orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('travel_approval_stages');
    }
};
