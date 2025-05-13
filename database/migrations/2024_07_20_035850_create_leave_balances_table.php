<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_leave_balances_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeaveBalancesTable extends Migration
{
    public function up()
    {
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('leave_type_id');
            $table->decimal('days_remaining', 4, 2)->default(0);
            $table->decimal('days_reserved', 4, 2)->default(0);
            $table->timestamps();
            $table->unique(['employee_id', 'leave_type_id']);

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('leave_type_id')->references('id')->on('leave_types')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('leave_balances');
    }
}
