<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overtime_order_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('overtime_order_id')->constrained('overtime_orders')->onDelete('cascade'); // Reference to overtime orders
            $table->string('file_path'); // Path to the uploaded file
            $table->string('file_name')->nullable(); // Optional file name for better readability
            $table->timestamps(); // Created and updated timestamps
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime_order_documents');
    }
};
