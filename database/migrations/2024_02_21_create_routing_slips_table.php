<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routing_slips', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('document_type', ['dtr', 'hr', 'leave', 'to']);
            $table->text('remarks')->nullable();
            $table->string('status');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('routing_slip_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('routing_slip_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type');
            $table->integer('file_size');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('routing_slip_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('routing_slip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->string('admin_type');
            $table->integer('sequence_number');
            $table->string('status')->default('pending');
            $table->text('remarks')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routing_slip_sequences');
        Schema::dropIfExists('routing_slip_files');
        Schema::dropIfExists('routing_slips');
    }
};
