<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_leave_accruals_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeaveAccrualsTable extends Migration
{
    public function up()
    {
        Schema::create('leave_accruals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('leave_type_id');
            $table->date('accrual_date');
            $table->decimal('days_accrued', 4, 2);
            $table->date('expiry_date')->nullable();
            $table->timestamps();
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('leave_type_id')->references('id')->on('leave_types')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('leave_accruals');
    }
}
