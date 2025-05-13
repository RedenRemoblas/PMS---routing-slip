<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add new columns to leaves table
        Schema::table('leaves', function (Blueprint $table) {
            if (!Schema::hasColumn('leaves', 'requires_alternate_approval')) {
                $table->boolean('requires_alternate_approval')->default(false)->after('leave_status');
            }
            if (!Schema::hasColumn('leaves', 'approval_level')) {
                $table->enum('approval_level', ['pending', 'alternate_approved', 'final_approved'])->default('pending')->after('requires_alternate_approval');
            }
        });

        // Add new columns to leave_approvers table if they don't exist
        if (Schema::hasTable('leave_approvers')) {
            Schema::table('leave_approvers', function (Blueprint $table) {
                if (!Schema::hasColumn('leave_approvers', 'alternate_approver_id')) {
                    $table->unsignedBigInteger('alternate_approver_id')->nullable()->after('approver_id');
                    $table->foreign('alternate_approver_id')->references('id')->on('employees')->onDelete('restrict');
                }
                if (!Schema::hasColumn('leave_approvers', 'approved_at')) {
                    $table->timestamp('approved_at')->nullable()->after('remarks');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropColumn(['requires_alternate_approval', 'approval_level']);
        });

        if (Schema::hasTable('leave_approvers')) {
            Schema::table('leave_approvers', function (Blueprint $table) {
                $table->dropForeign(['alternate_approver_id']);
                $table->dropColumn(['alternate_approver_id', 'approved_at']);
            });
        }
    }
};
