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
        Schema::create('dtr_adjustment_entries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('request_id'); // Or use uuid('request_id') for UUIDs

            $table->datetime('adjustment_datetime'); // Combines date and time into a single field
            $table->string('logType', 32); // Log type (e.g., IN or OUT)
            $table->text('reason'); // Reason for the adjustment
            $table->text('remarks')->nullable(); // Optional remarks
            $table->timestamps();

            $table->foreign('request_id')->references('id')->on('dtr_adjustment_requests')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dtr_adjustment_entries');
    }
};
