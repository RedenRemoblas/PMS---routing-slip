<?php

namespace App\Models\Hr;

use App\Models\Employee;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Leave extends Model
{
    use HasFactory;

    protected $with = ['leaveDetails'];

    protected $fillable = [
        'date_filed',
        'employee_id',
        'leave_type_id',
        'description',
        'details',
        'total_days',
        'commutation',
        'leave_status',
        'application_file_path',
        'uploaded_file_path',
        'requires_alternate_approval',
        'approval_level',
    ];

    protected $attributes = [
        'leave_status' => 'pending', // Default status is 'pending'
        'approval_level' => 'pending', // Default approval level is 'pending'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function leaveDetails()
    {
        return $this->hasMany(LeaveDetail::class);
    }

    public function leaveUsages()
    {
        return $this->hasMany(LeaveUsage::class);
    }

    public function approvers()
    {
        return $this->hasMany(LeaveApprover::class);
    }

    public function addApprover($approverId, $alternateApproverId = null)
    {
        return $this->approvers()->create([
            'approver_id' => $approverId,
            'alternate_approver_id' => $alternateApproverId,
            'status' => 'pending'
        ]);
    }

    public function isApproved(): bool
    {
        return $this->leave_status === 'approved' && $this->approval_level === 'final_approved';
    }

    public function isPending(): bool
    {
        return $this->leave_status === 'pending';
    }

    public function needsAlternateApproval(): bool
    {
        return $this->requires_alternate_approval && $this->approval_level === 'pending';
    }

    public function canBeApprovedByOriginal(): bool
    {
        return !$this->requires_alternate_approval || 
               ($this->requires_alternate_approval && $this->approval_level === 'alternate_approved');
    }

    public function approveLeave(): void
    {
        // Update leave status
        $this->update(['leave_status' => 'approved']);
    }

    public function lockLeave()
    {

        // Assign approvers before locking
        //   $this->assignApprovers();
        // Update the status to 'locked'
        $this->update(['leave_status' => 'locked']);



        Log::info('Leave locked and approvers assigned', ['leave' => $this]);
    }

    public static function checkLeaveBalance(int $employeeId, int $leaveTypeId, float $totalDaysRequested, float $currentTotalDaysReserved = 0): void
    {
        $leaveBalance = LeaveBalance::where('employee_id', $employeeId)
            ->where('leave_type_id', $leaveTypeId)
            ->first();

        if ($leaveBalance) {
            $availableDays = $leaveBalance->days_remaining - ($leaveBalance->days_reserved - $currentTotalDaysReserved);
        } else {
            // Handle the case where $leaveBalance is null
            $availableDays = 0; // or any other default value
            // Optionally, log an error or throw an exception
        }

        if (! $leaveBalance || $availableDays < $totalDaysRequested) {
            throw ValidationException::withMessages([
                'leave_balance' => 'You do not have enough leave balance for the requested leave days.',
            ]);
        }
    }

    /**
     * Reserve the leave days in the leave balance.
     */
    public static function reserveLeaveDays(int $employeeId, int $leaveTypeId, float $totalDaysReserved): void
    {
        $leaveBalance = LeaveBalance::where('employee_id', $employeeId)
            ->where('leave_type_id', $leaveTypeId)
            ->first();

        if ($leaveBalance) {
            $leaveBalance->days_reserved += $totalDaysReserved;
            Log::info("Reserving {$totalDaysReserved} days for employee ID {$employeeId}, leave type ID {$leaveTypeId}. New reserved days: {$leaveBalance->days_reserved}");
            $leaveBalance->save();
        }
    }

    /**
     * Deduct the leave days from the reserved days in the leave balance.
     */
    public static function deductReservedLeaveDays(int $employeeId, int $leaveTypeId, float $totalDaysReserved): void
    {
        $leaveBalance = LeaveBalance::where('employee_id', $employeeId)
            ->where('leave_type_id', $leaveTypeId)
            ->first();

        if ($leaveBalance) {
            Log::info("Before Deducting {$totalDaysReserved} days for employee ID {$employeeId}, leave type ID {$leaveTypeId}. old reserved days: {$leaveBalance->days_reserved}");
            $leaveBalance->days_reserved -= $totalDaysReserved;
            Log::info("Deducting {$totalDaysReserved} days for employee ID {$employeeId}, leave type ID {$leaveTypeId}. New reserved days: {$leaveBalance->days_reserved}");
            $leaveBalance->save();
        }
    }

    /**
     * Update the leave balance within a transaction, handling changes in leave type.
     */
    /* public static function updateLeaveBalance(int $employeeId, int $oldLeaveTypeId, float $previousTotalDays, int $newLeaveTypeId, float $newTotalDays): void
    {
        DB::transaction(function () use ($employeeId, $oldLeaveTypeId, $previousTotalDays, $newLeaveTypeId, $newTotalDays) {
            Log::info("Starting transaction to update leave balance for employee ID {$employeeId}. Old leave type ID: {$oldLeaveTypeId}, previous total days: {$previousTotalDays}. New leave type ID: {$newLeaveTypeId}, new total days: {$newTotalDays}");
            self::deductReservedLeaveDays($employeeId, $oldLeaveTypeId, $previousTotalDays);
            self::reserveLeaveDays($employeeId, $newLeaveTypeId, $newTotalDays);
            Log::info("Transaction completed for updating leave balance for employee ID {$employeeId}.");
        });
    }  */

    protected static function boot()
    {
        parent::boot();




        static::updated(function ($leave) {
            Log::info('Leave updated', ['leave' => $leave]);
            if ($leave->isDirty('leave_status')) {
                if ($leave->leave_status === 'approved') {
                    $leave->deductReservedDaysAndLogUsage();
                }
            }
        });
    }

    /**
     * Deduct reserved leave days from the remaining balance and log the usage.
     *
     * @return void
     */
    public function deductReservedDaysAndLogUsage()
    {
        // Retrieve the leave balance for the employee and leave type
        $leaveBalance = LeaveBalance::where('employee_id', $this->employee_id)
            ->where('leave_type_id', $this->leave_type_id)
            ->first();

        if ($leaveBalance) {
            // Update the leave balance by deducting the reserved and remaining days
            $leaveBalance->update([
                'days_reserved' => $leaveBalance->days_reserved - $this->total_days,
                'days_remaining' => $leaveBalance->days_remaining - $this->total_days,
            ]);

            // Log the updated leave balance
            Log::info('Reserved days deducted from remaining balance.', [
                'days_reserved' => $leaveBalance->days_reserved,
                'days_remaining' => $leaveBalance->days_remaining,
            ]);

            // Collect the leave dates from the leave details
            $leaveDates = $this->leaveDetails->pluck('leave_date')->implode(', ');

            // Record the leave usage in the LeaveUsage table
            LeaveUsage::create([
                'employee_id' => $this->employee_id,
                'leave_type_id' => $this->leave_type_id,
                'dates' => $leaveDates,
                'days_used' => $this->total_days,
            ]);

            // Log the leave usage recording
            Log::info('Leave usage recorded in LeaveUsage table.', [
                'employee_id' => $this->employee_id,
                'leave_type_id' => $this->leave_type_id,
                'dates' => $leaveDates,
                'days_used' => $this->total_days,
            ]);
        }
    }

    public function updateApplicationFilePath(string $filePath)
    {
        $this->update(['application_file_path' => $filePath]);
        Log::info("Updated application_file_path for Leave ID {$this->id}: {$filePath}");
    }

    public function updateUploadedFilePath(string $filePath)
    {
        $this->update(['uploaded_file_path' => $filePath]);
        Log::info("Updated uploaded_file_path for Leave ID {$this->id}: {$filePath}");
    }
}
