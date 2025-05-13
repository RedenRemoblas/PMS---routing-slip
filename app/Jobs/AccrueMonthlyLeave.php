<?php

namespace App\Jobs;

use App\Models\Employee;
use App\Models\Hr\LeaveAccrual;
use App\Models\Hr\LeaveType;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AccrueMonthlyLeave implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        Log::info("AccrueMonthlyLeave job booted.");

        $currentMonth = Carbon::now()->startOfMonth();
        $leaveTypes = LeaveType::where('frequency', '!=', 'event_based')->get();
        $employees = Employee::where('is_active', true)
            ->where('employment_status', 'plantilla')
            ->get();

        foreach ($employees as $employee) {
            foreach ($leaveTypes as $leaveType) {
                $accrualRate = $leaveType->accrual_rate;

                if ($accrualRate <= 0 || $accrualRate === null) {
                    continue;
                }

                if ($leaveType->frequency === 'yearly' && $currentMonth->month !== 1) {
                    continue;
                }

                $existingAccrual = LeaveAccrual::where('employee_id', $employee->id)
                    ->where('leave_type_id', $leaveType->id)
                    ->whereYear('accrual_date', $currentMonth->year)
                    ->whereMonth('accrual_date', $currentMonth->month)
                    ->exists();

                if ($existingAccrual) {
                    Log::info("Skipping leave accrual for Employee ID: {$employee->id}, Leave Type ID: {$leaveType->id} - Already accrued.");
                    continue;
                }

                $expiryDate = null;
                if ($leaveType->fixed_expiry) {
                    $expiryDate = Carbon::createFromFormat('Y-m-d', now()->year . '-' . $leaveType->fixed_expiry);
                } elseif ($leaveType->expiration_days) {
                    $expiryDate = $currentMonth->copy()->addDays($leaveType->expiration_days);
                }

                Log::info("Creating leave accrual for Employee ID: {$employee->id}, Leave Type ID: {$leaveType->id}");

                LeaveAccrual::createAccrual([
                    'employee_id' => $employee->id,
                    'leave_type_id' => $leaveType->id,
                    'accrual_date' => $currentMonth,
                    'days_accrued' => $accrualRate,
                    'expiry_date' => $expiryDate,
                ]);
            }
        }
    }
}
