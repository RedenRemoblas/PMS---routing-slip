<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Hr\LeaveAccrual;
use App\Models\Hr\LeaveBalance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HandleLeaveExpiration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("HandleLeaveExpiration job started.");

        DB::transaction(function () {
            // ✅ Process expired leave accruals in batches to improve efficiency
            LeaveAccrual::whereNotNull('expiry_date')
                ->where('expiry_date', '<', now())
                ->chunkById(100, function ($expiredAccruals) {
                    foreach ($expiredAccruals as $accrual) {
                        $employeeId = $accrual->employee_id;
                        $leaveTypeId = $accrual->leave_type_id;
                        $expiredDays = $accrual->days_accrued;

                        // ✅ Fetch the corresponding leave balance
                        $leaveBalance = LeaveBalance::where('employee_id', $employeeId)
                            ->where('leave_type_id', $leaveTypeId)
                            ->lockForUpdate()
                            ->first();

                        if ($leaveBalance) {
                            $newDaysRemaining = max(0, $leaveBalance->days_remaining - $expiredDays);

                            // ✅ Only update the balance if necessary
                            if ($newDaysRemaining !== $leaveBalance->days_remaining) {
                                $leaveBalance->update([
                                    'days_remaining' => $newDaysRemaining,
                                ]);
                                Log::info("Deducted {$expiredDays} expired days for Employee ID: {$employeeId}, Leave Type ID: {$leaveTypeId}. New balance: {$newDaysRemaining}");
                            }
                        } else {
                            Log::warning("Leave balance not found for Employee ID: {$employeeId}, Leave Type ID: {$leaveTypeId}");
                        }

                        // ✅ Delete accrual *after* leave balance is successfully updated
                        $accrual->delete();
                        Log::info("Deleted expired leave accrual ID {$accrual->id} for Employee ID: {$employeeId}, Leave Type ID: {$leaveTypeId}");
                    }
                });
        });

        Log::info("HandleLeaveExpiration job completed.");
    }
}
