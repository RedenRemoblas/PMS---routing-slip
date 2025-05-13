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
        Schema::table('routing_slip_sequences', function (Blueprint $table) {
            $table->string('admin_type')->nullable()->default('ACCOUNTANT')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('routing_slip_sequences', function (Blueprint $table) {
            $table->string('admin_type')->nullable(false)->change();
        });
    }
};