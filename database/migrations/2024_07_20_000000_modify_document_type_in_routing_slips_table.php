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
        Schema::table('routing_slips', function (Blueprint $table) {
            // Drop the enum constraint and change to a string with sufficient length
            $table->string('document_type', 50)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('routing_slips', function (Blueprint $table) {
            // Revert back to enum (note: this might cause data loss if new values were added)
            $table->enum('document_type', ['dtr', 'hr', 'leave', 'to'])->change();
        });
    }
};