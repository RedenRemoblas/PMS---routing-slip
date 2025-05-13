<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCocApplicationsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('coc_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->date('date_filed');
            $table->text('description');
            $table->enum('status', ['Pending', 'Locked', 'Approved', 'Disapproved', 'Cancelled'])->default('Pending'); // Status of overtime order
            $table->integer('current_stage')->default(1);
            $table->timestamps();
        });

        Schema::create('coc_application_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coc_application_id')->constrained('coc_applications')->onDelete('cascade');
            $table->date('date_earned');
            $table->integer('hours_earned');
            $table->foreignId('travel_order_id')->nullable()->constrained('travel_orders')->onDelete('set null');
            $table->foreignId('overtime_order_id')->nullable()->constrained('overtime_orders')->onDelete('set null');
            $table->timestamps();

            $table->unique(['coc_application_id', 'date_earned']);
        });

        Schema::create('coc_approval_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coc_application_id')->constrained('coc_applications')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');

            $table->enum('status', ['Pending', 'Locked', 'Approved', 'Rejected', 'Completed'])->default('Pending'); // Field for status
            $table->text('remarks')->nullable();
            $table->integer('sequence');
            $table->timestamps();

            // Add unique constraints
            $table->unique(['coc_application_id', 'employee_id']);
            $table->unique(['coc_application_id', 'sequence']);
        });

        /*  Schema::create('coc_default_approvers', function (Blueprint $table) {
            $table->id();

            $table->integer('division_id')->unsigned()->nullable();
            $table->foreign('division_id')->references('id')->on('divisions');

            //    $table->foreignId('division_id')->constrained('divisions')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->integer('sequence');
            $table->timestamps();

            $table->unique(['division_id', 'sequence']);
        });*/
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::dropIfExists('coc_approval_stages');
        Schema::dropIfExists('coc_application_details');
        Schema::dropIfExists('coc_default_approvers');
        Schema::dropIfExists('coc_applications');
    }
}
