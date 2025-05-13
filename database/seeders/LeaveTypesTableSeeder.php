<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Hr\LeaveType;
use Illuminate\Support\Facades\File;

class LeaveTypesTableSeeder extends Seeder
{
    public function run()
    {
        $jsonPath = database_path('leaves.json');

        if (!File::exists($jsonPath)) {
            return;
        }

        $json = File::get($jsonPath);
        $rows = json_decode($json, true);

        foreach ($rows as $row) {
            LeaveType::updateOrCreate(
                ['leave_name' => $row['leave_name']], // Ensure uniqueness
                $row
            );
        }
    }
}
