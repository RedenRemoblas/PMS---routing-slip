<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\Hr\Dtr;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DtrTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all employees
        $employees = Employee::all();

        // Define the start and end dates for one month
        $startDate = Carbon::now()->subMonth()->startOfMonth();
        $endDate = Carbon::now()->subMonth()->endOfMonth();

        // Loop through each employee
        foreach ($employees as $employee) {
            // Loop through each day of the month
            for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                // Skip weekends (Saturday and Sunday)
                if ($date->isWeekend()) {
                    continue;
                }

                // Randomize the minutes and seconds for time-in and time-out
                $morningInMinutes = rand(0, 10); // Variation between 0 to 10 minutes
                $morningInSeconds = rand(0, 59);

                $lunchOutMinutes = rand(0, 5); // Variation between 0 to 5 minutes
                $lunchOutSeconds = rand(0, 59);

                $lunchInMinutes = rand(0, 5); // Variation between 0 to 5 minutes
                $lunchInSeconds = rand(0, 59);

                $eveningOutMinutes = rand(0, 10); // Variation between 0 to 10 minutes
                $eveningOutSeconds = rand(0, 59);

                // Morning Time-In
                Dtr::create([
                    'id' => Str::uuid(),
                    'dtr_timestamp' => $date->copy()->setTime(8, $morningInMinutes, $morningInSeconds), // 8:00 AM with variation
                    'log_type' => 'IN',
                    'employee_dtr_no' => $employee->dtr_no,
                    'device_serial_no' => '12345',
                    'verify_mode' => 'f',
                    'sequence_no' => 1,
                    'remarks' => 'Morning entry',
                ]);

                // Lunch Break-Out
                Dtr::create([
                    'id' => Str::uuid(),
                    'dtr_timestamp' => $date->copy()->setTime(12, $lunchOutMinutes, $lunchOutSeconds), // 12:00 PM with variation
                    'log_type' => 'BREAK OUT',
                    'employee_dtr_no' => $employee->dtr_no,
                    'device_serial_no' => '12345',
                    'verify_mode' => 'f',
                    'sequence_no' => 2,
                    'remarks' => 'Lunch break-out',
                ]);

                // Lunch Break-In
                Dtr::create([
                    'id' => Str::uuid(),
                    'dtr_timestamp' => $date->copy()->setTime(13, $lunchInMinutes, $lunchInSeconds), // 1:00 PM with variation
                    'log_type' => 'BREAK IN',
                    'employee_dtr_no' => $employee->dtr_no,
                    'device_serial_no' => '12345',
                    'verify_mode' => 'f',
                    'sequence_no' => 3,
                    'remarks' => 'Lunch break-in',
                ]);

                // Evening Time-Out
                Dtr::create([
                    'id' => Str::uuid(),
                    'dtr_timestamp' => $date->copy()->setTime(17, $eveningOutMinutes, $eveningOutSeconds), // 5:00 PM with variation
                    'log_type' => 'OUT',
                    'employee_dtr_no' => $employee->dtr_no,
                    'device_serial_no' => '12345',
                    'verify_mode' => 'f',
                    'sequence_no' => 4,
                    'remarks' => 'Evening entry',
                ]);
            }
        }
    }
}
