<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routing_slip_files', function (Blueprint $table) {
            $table->foreignId('uploaded_by')->nullable()->after('file_size')->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::table('routing_slip_files', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by']);
            $table->dropColumn('uploaded_by');
        });
    }
};