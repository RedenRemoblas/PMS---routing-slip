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

        Schema::create('holidays', function (Blueprint $table) {
            $table->increments('id');
            $table->enum('type', ['legal', 'special']);
            $table->string('description');
            $table->date('holiday_date');
            $table->time('time_start')->nullable();
            $table->time('time_end')->nullable();
            $table->boolean('is_whole_day')->unsigned()->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
