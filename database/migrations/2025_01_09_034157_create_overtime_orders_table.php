<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create the overtime_orders table
        Schema::create('overtime_orders', function (Blueprint $table) {
            $table->id();
            $table->text('purpose'); // Purpose of overtime, including dates if necessary
            $table->date('date_filed'); // Date the overtime was filed
            $table->enum('status', ['Pending', 'Locked', 'Approved', 'Disapproved', 'Cancelled'])->default('Pending'); // Status of overtime order
            $table->foreignId('created_by')->constrained('employees')->onDelete('cascade'); // User who created the overtime order
            $table->timestamps(); // Created and updated timestamps
        });

        // Create the overtime_order_details table
        Schema::create('overtime_order_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('overtime_order_id')->constrained('overtime_orders')->onDelete('cascade'); // Reference to overtime order
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade'); // Reference to employee
            $table->string('position', 100)->nullable(); // Employee position
            $table->string('division', 100)->nullable(); // Employee division
            $table->decimal('hours_rendered', 5, 2); // Number of hours rendered
            $table->timestamps(); // Created and updated timestamps
        });
    }

    public function down(): void
    {
        // Drop the overtime_order_details table first to avoid foreign key constraint issues
        Schema::dropIfExists('overtime_order_details');

        // Drop the overtime_orders table
        Schema::dropIfExists('overtime_orders');
    }
};
