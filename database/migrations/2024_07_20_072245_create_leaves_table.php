<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->date('date_filed');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('leave_type_id');
            $table->string('details')->nullable();
            $table->string('description')->nullable();
            $table->string('commutation')->nullable();
            $table->integer('total_days')->default(0);

            $table->enum('leave_status', ['pending', 'disapproved', 'approved', 'cancelled', 'locked', 'completed'])->default('pending');

            $table->string('application_file_path')->nullable();
            $table->string('uploaded_file_path')->nullable();

            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('restrict');
            $table->foreign('leave_type_id')->references('id')->on('leave_types')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaves');
    }
};
