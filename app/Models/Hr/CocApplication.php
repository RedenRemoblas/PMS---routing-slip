<?php

namespace App\Models\Hr;

use App\Models\Employee;
use App\Models\DefaultApprover;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CocApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'date_filed',
        'description',
        'status',
        'current_stage',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($cocApplication) {
            // Fetch default approvers based on the division of the employee
            $defaultApprovers = DefaultApprover::where('division_id', $cocApplication->employee->division_id)
                ->orderBy('sequence')
                ->get();

            foreach ($defaultApprovers as $defaultApprover) {
                $cocApplication->approvalStages()->create([
                    'employee_id' => $defaultApprover->employee_id,
                    'status' => 'pending',
                    'sequence' => $defaultApprover->sequence,
                ]);
            }
        });
    }

    /**
     * Approve or reject the COC application.
     *
     * @param bool $isApproved
     * @throws \Exception
     */
    public function approveApplication(bool $isApproved): void
    {
        $currentStage = $this->approvalStages()
            ->where('employee_id', auth()->id())
            ->where('status', 'pending')
            ->first();

        if (!$currentStage) {
            throw new \Exception('You are not authorized to approve or reject this stage.');
        }

        if ($isApproved) {
            $currentStage->update(['status' => 'approved']);
            $this->processApproval();

            if ($this->approvalStages()->where('status', 'pending')->doesntExist()) {
                $this->update(['status' => 'approved']);
            }
        } else {
            $currentStage->update(['status' => 'rejected']);
            $this->update(['status' => 'rejected']);
        }
    }

    /**
     * Process approval by adding leave accrual for the employee.
     */
    protected function processApproval(): void
    {
        $ctoLeaveType = LeaveType::where('leave_name', 'Compensatory Time Off (CTO)')->first();

        if (!$ctoLeaveType) {
            Log::warning('CTO leave type not found.');
            return;
        }

        foreach ($this->details as $detail) {
            LeaveAccrual::createAccrual([
                'employee_id' => $this->employee_id,
                'leave_type_id' => $ctoLeaveType->id,
                'accrual_date' => $detail->date_earned,
                'days_accrued' => $detail->hours_earned / 8, // Assuming 8 hours per day
                'expiry_date' => null, // Add expiry if required
            ]);
        }
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function details()
    {
        return $this->hasMany(CocApplicationDetail::class);
    }

    public function approvalStages()
    {
        return $this->hasMany(CocApprovalStage::class);
    }

    public function currentApprovalStage()
    {
        return $this->approvalStages()
            ->where('status', 'pending')
            ->orderBy('sequence')
            ->first();
    }

    public function currentApprovalStageForUser($userId)
    {
        return $this->approvalStages()
            ->where('employee_id', $userId)
            ->where('status', 'pending')
            ->orderBy('sequence')
            ->first();
    }

    public function lock()
    {
        if ($this->status === 'Locked') {
            Log::warning('COC application already locked', ['coc_application' => $this]);
            return false;
        }

        // Check if there are default approvers for the determined division
        if (!$this->hasDefaultApprovers()) {
            Log::warning('No default approvers found for the division', ['coc_application' => $this]);
            return false;
        }

        // Update the status to 'locked'
        $this->update(['status' => 'Locked']);

        // Assign approvers after locking
        $this->assignApprovers();

        Log::info('COC application locked and approvers assigned', ['coc_application' => $this]);
        return true;
    }

    public function hasDefaultApprovers()
    {
        $divisionId = $this->employee->division_id;

        if (!$divisionId) {
            Log::warning('No division found for default approvers', ['coc_application' => $this]);
            return false;
        }

        return DefaultApprover::where('division_id', $divisionId)->exists();
    }

    public function assignApprovers()
    {
        Log::info('Assigning approvers to COC application', ['coc_application' => $this]);

        $defaultApprovers = DefaultApprover::where('division_id', $this->employee->division_id)
            ->orderBy('sequence')
            ->get();

        if ($defaultApprovers->isEmpty()) {
            Log::warning('No default approvers found for the division', ['division_id' => $this->employee->division_id]);
            return;
        }

        foreach ($defaultApprovers as $defaultApprover) {
            $existingStage = $this->approvalStages()
                ->where('employee_id', $defaultApprover->employee_id)
                ->where('sequence', $defaultApprover->sequence)
                ->exists();

            if (!$existingStage) {
                $this->approvalStages()->create([
                    'employee_id' => $defaultApprover->employee_id,
                    'status' => 'pending',
                    'sequence' => $defaultApprover->sequence,
                ]);
            } else {
                Log::info('Skipping duplicate approver assignment', [
                    'employee_id' => $defaultApprover->employee_id,
                    'coc_application_id' => $this->id,
                ]);
            }
        }

        Log::info('Approvers assigned to COC application', ['coc_application' => $this]);
    }
}
