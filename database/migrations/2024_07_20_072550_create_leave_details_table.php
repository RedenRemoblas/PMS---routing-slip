<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('leave_id');
            $table->date('leave_date');
            $table->enum('period', ['am', 'pm', 'wd']); // 'am' for morning, 'pm' for afternoon, 'wd' for whole day
            $table->decimal('qty', 4, 2); // Quantity of leave taken, e.g., 1 for a full day, 0.5 for half-day
            $table->timestamps();
            $table->unique(['leave_id', 'leave_date']); // Add unique constraint
            $table->foreign('leave_id')->references('id')->on('leaves')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_details');
    }
};
