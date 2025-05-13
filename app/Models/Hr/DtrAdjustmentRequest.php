<?php

namespace App\Models\Hr;


use App\Models\Employee;
use Illuminate\Support\Str;
use App\Models\DefaultApprover;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DtrAdjustmentRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'purpose',
        'month_year',
        'reason',
        'status',
        'created_by',
    ];

    /**
     * Relationships
     */

    // Creator of the DTR adjustment request
    public function creator()
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    // Details of the DTR adjustment entries
    public function details()
    {
        return $this->hasMany(DtrAdjustmentEntry::class, 'request_id');
    }

    // Approval stages of the DTR adjustment request
    public function approvalStages()
    {
        return $this->hasMany(DtrAdjustmentApprovalStage::class, 'dtr_adjustment_request_id');
    }

    /**
     * Assign default approvers to the DTR adjustment request
     */
    public function assignApprovers()
    {
        Log::info('Assigning approvers to DTR adjustment request', ['request_id' => $this->id]);

        $divisionId = $this->employee->division_id;

        if (!$divisionId) {
            Log::warning('No division found for assigning approvers', ['request_id' => $this->id]);
            return;
        }

        $defaultApprovers = DefaultApprover::where('division_id', $divisionId)
            ->orderBy('sequence')
            ->get();

        if ($defaultApprovers->isEmpty()) {
            Log::warning('No default approvers found for the division', ['division_id' => $divisionId]);
            return;
        }

        foreach ($defaultApprovers as $approver) {
            $this->approvalStages()->create([
                'employee_id' => $approver->employee_id,
                'status' => 'pending',
                'sequence' => $approver->sequence,
                'division_id' => $approver->division_id,
            ]);
        }

        Log::info('Approvers assigned for DTR adjustment request', ['request_id' => $this->id]);
    }

    /**
     * Lock the DTR adjustment request
     */
    public function lockRequest()
    {
        if ($this->status === 'Locked') {
            Log::warning('DTR adjustment request already locked', ['request_id' => $this->id]);
            return false;
        }

        if (!$this->hasDefaultApprovers()) {
            Log::warning('No default approvers found for the division', ['request_id' => $this->id]);
            return false;
        }

        $this->update(['status' => 'Locked']);
        $this->assignApprovers();

        Log::info('DTR adjustment request locked and approvers assigned', ['request_id' => $this->id]);
        return true;
    }

    /**
     * Check if default approvers exist for the division
     */
    public function hasDefaultApprovers()
    {
        $divisionId = $this->employee->division_id;

        if (!$divisionId) {
            Log::warning('No division found for default approvers', ['request_id' => $this->id]);
            return false;
        }

        return DefaultApprover::where('division_id', $divisionId)->exists();
    }






    /**
     * Sync approved DTR adjustment entries to the DTR table.
     */
    public function syncApprovedEntriesToDtr()
    {
        foreach ($this->details as $entry) {
            // Insert the adjustment entry into the DTR table
            DB::table('dtrs')->insert([
                'id' => (string) Str::uuid(), // Generate a unique ID
                'dtr_timestamp' => $entry->adjustment_datetime,
                'log_type' => $entry->logType,
                'employee_dtr_no' => $this->creator->employee_no, // Assuming creator has an `employee_number` field
                'device_serial_no' => 'Manual Adjustment', // Indicate this as a manual adjustment
                'verify_mode' => 'MANUAL',
                'sequence_no' => 0, // Adjust as necessary
                'remarks' => $entry->remarks ?? 'Adjusted via DTR Adjustment Request',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Log::info('Approved DTR adjustment entries synced to DTR table', ['request_id' => $this->id]);
    }

    /**
     * Approve the current stage for the given user.
     */
    public function approveCurrentStage($userId)
    {
        $currentStage = $this->currentApprovalStageForUser($userId);

        if (!$currentStage) {
            Log::warning('No pending approval stage found for user', ['user_id' => $userId, 'request_id' => $this->id]);
            return false;
        }

        $currentStage->update(['status' => 'approved']);

        $nextStage = $this->currentApprovalStage();

        if (!$nextStage) {
            $this->update(['status' => 'Approved']);

            // Sync approved entries to DTR table
            $this->syncApprovedEntriesToDtr();

            Log::info('DTR adjustment request fully approved', ['request_id' => $this->id]);
            return true;
        }

        Log::info('Approval stage completed. Waiting for next approver.', ['request_id' => $this->id]);
        return true;
    }

    /**
     * Reject the current stage for the given user
     */
    public function rejectCurrentStage($userId)
    {
        $currentStage = $this->currentApprovalStageForUser($userId);

        if (!$currentStage) {
            Log::warning('No pending approval stage found for user', ['user_id' => $userId, 'request_id' => $this->id]);
            return false;
        }

        $currentStage->update(['status' => 'rejected']);

        $this->update(['status' => 'Rejected']);
        Log::info('DTR adjustment request rejected', ['request_id' => $this->id]);
        return true;
    }

    /**
     * Get the current pending approval stage
     */
    public function currentApprovalStage()
    {
        return $this->approvalStages()
            ->where('status', 'pending')
            ->orderBy('sequence')
            ->first();
    }

    /**
     * Get the current pending approval stage for a specific user
     */
    public function currentApprovalStageForUser($userId)
    {
        return $this->approvalStages()
            ->where('employee_id', $userId)
            ->where('status', 'pending')
            ->orderBy('sequence')
            ->first();
    }
    public function entries()
    {
        return $this->hasMany(DtrAdjustmentEntry::class, 'request_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }
}
