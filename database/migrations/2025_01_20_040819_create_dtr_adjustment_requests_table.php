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
        Schema::create('dtr_adjustment_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('month_year', 7); // Format 'YYYY-MM'
            $table->unsignedBigInteger('created_by'); // Employee ID
            $table->enum('status', ['Pending', 'Approved', 'Rejected', 'Cancelled', 'Locked']);

            $table->timestamps();

            // Foreign key constraint
            $table->foreign('created_by')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dtr_adjustment_requests');
    }
};
