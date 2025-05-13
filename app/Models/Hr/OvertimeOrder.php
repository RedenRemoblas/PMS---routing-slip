<?php

namespace App\Models\Hr;

use App\Models\Employee;
use App\Models\DefaultApprover;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OvertimeOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'purpose',
        'date_filed',
        'status',
        'created_by',
    ];

    public function creator()
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function details()
    {
        return $this->hasMany(OvertimeOrderDetail::class, 'overtime_order_id');
    }

    public function approvalStages()
    {
        return $this->hasMany(OvertimeApprovalStage::class, 'overtime_order_id');
    }

    public function documents()
    {
        return $this->hasMany(OvertimeOrderDocument::class);
    }

    public function assignApprovers()
    {
        Log::info('Assigning approvers', ['overtime_order' => $this]);

        $divisionId = $this->getDivisionWithMostEmployees();

        if (!$divisionId) {
            Log::warning('No division found for assigning approvers', ['overtime_order' => $this]);
            return;
        }

        // Fetch default approvers based on the determined division
        $defaultApprovers = DefaultApprover::where('division_id', $divisionId)
            ->orderBy('sequence')
            ->get();

        if ($defaultApprovers->isEmpty()) {
            Log::warning('No default approvers found for the division', ['division_id' => $divisionId]);
            return;
        }

        foreach ($defaultApprovers as $defaultApprover) {
            $this->approvalStages()->create([
                'employee_id' => $defaultApprover->employee_id,
                'status' => 'pending',
                'sequence' => $defaultApprover->sequence,
                'division_id' => $defaultApprover->division_id,
            ]);
        }

        Log::info('Approvers assigned from division', ['division_id' => $divisionId]);
    }

    protected function getDivisionWithMostEmployees()
    {
        $divisionCounts = $this->details()
            ->with(['employee', 'employee.division'])
            ->get()
            ->groupBy('employee.division_id')
            ->map(fn($group) => $group->count())
            ->sortDesc();

        return $divisionCounts->keys()->first();
    }

    public function lockOvertimeOrder()
    {
        if ($this->status === 'Locked') {
            Log::warning('Overtime order already locked', ['overtime_order' => $this]);
            return false;
        }

        if (!$this->hasDefaultApprovers()) {
            Log::warning('No default approvers found for the division', ['overtime_order' => $this]);
            return false;
        }

        $this->update(['status' => 'Locked']);

        $this->assignApprovers();

        Log::info('Overtime order locked and approvers assigned', ['overtime_order' => $this]);
        return true;
    }

    public function hasDefaultApprovers()
    {
        $divisionId = $this->getDivisionWithMostEmployees();

        if (!$divisionId) {
            Log::warning('No division found for default approvers', ['overtime_order' => $this]);
            return false;
        }

        return DefaultApprover::where('division_id', $divisionId)->exists();
    }

    public function approveCurrentStage($userId)
    {
        $currentStage = $this->currentApprovalStageForUser($userId);

        if (!$currentStage) {
            Log::warning('No pending approval stage found for user', ['user_id' => $userId, 'overtime_order' => $this]);
            return false;
        }

        $currentStage->update(['status' => 'approved']);

        $nextStage = $this->currentApprovalStage();

        if (!$nextStage) {
            $this->update(['status' => 'Approved']);
            Log::info('Overtime order approved completely', ['overtime_order' => $this]);
            return true;
        }

        Log::info('Approval stage completed. Waiting for next approver.', ['overtime_order' => $this]);
        return true;
    }

    public function rejectCurrentStage($userId)
    {
        $currentStage = $this->currentApprovalStageForUser($userId);

        if (!$currentStage) {
            Log::warning('No pending approval stage found for user', ['user_id' => $userId, 'overtime_order' => $this]);
            return false;
        }

        $currentStage->update(['status' => 'disapproved']);

        $this->update(['status' => 'Disapproved']);
        Log::info('Overtime order disapproved', ['overtime_order' => $this]);
        return true;
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
}
