<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_orders', function (Blueprint $table) {
            $table->id();

            $table->date('inclusive_start_date');
            $table->date('inclusive_end_date');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');

            $table->text('purpose');
            $table->string('official_vehicle', 20)->nullable();
            $table->string('funding_type', 100); // Field for funding type
            $table->enum('status', ['Pending', 'Locked', 'Approved', 'Rejected', 'Completed'])->default('Pending'); // Field for status
            $table->text('remarks')->nullable(); // Field for remarks
            $table->text('place_of_origin'); // Field for place of origin
            $table->text('destination'); // Field for destination
            $table->decimal('farthest_distance', 8, 2)->nullable(); // Field for farthest distance
            $table->date('date_approved')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_orders');
    }
};
