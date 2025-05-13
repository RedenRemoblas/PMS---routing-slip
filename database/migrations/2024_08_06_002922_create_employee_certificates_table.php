// database/migrations/xxxx_xx_xx_xxxxxx_create_employee_certificates_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeCertificatesTable extends Migration
{
    public function up()
    {
        Schema::create('employee_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->text('private_key');
            $table->text('certificate');
            $table->text('intermediate_certificates')->nullable();
            $table->string('signature_image_path');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('employee_certificates');
    }
}
