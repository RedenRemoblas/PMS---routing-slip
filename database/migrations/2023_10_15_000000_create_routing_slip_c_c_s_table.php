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
        Schema::create('routing_slip_c_c_s', function (Blueprint $table) {
            $table->id();
            $table->foreignId('routing_slip_id')->constrained('routing_slips')->onDelete('cascade');
            $table->string('name');
            $table->string('position');
            $table->string('division');
            $table->string('email')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routing_slip_c_c_s');
    }
};