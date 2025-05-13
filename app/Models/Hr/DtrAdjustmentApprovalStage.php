<?php

namespace App\Models\Hr;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DtrAdjustmentApprovalStage extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'dtr_adjustment_approval_stages';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'employee_id',
        'dtr_adjustment_request_id',
        'status',
        'remarks',
        'sequence',
    ];

    /**
     * Relationships
     */

    /**
     * Get the employee associated with the DTR adjustment approval stage.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the DTR adjustment request associated with the approval stage.
     */
    public function dtrAdjustment()
    {
        return $this->belongsTo(DtrAdjustmentRequest::class, 'dtr_adjustment_request_id');
    }

    /**
     * Approve the current stage and move to the next stage.
     */
    public function approve()
    {
        // Update current stage to approved
        $this->update(['status' => 'approved']);

        $nextStage = $this->dtrAdjustment->approvalStages()
            ->where('sequence', '>', $this->sequence)
            ->orderBy('sequence')
            ->first();

        if ($nextStage === null) {
            // All stages completed, mark DTR adjustment as approved
            $this->dtrAdjustment->update(['status' => 'approved']);
        } else {
            // Move to the next stage
            $nextStage->update(['status' => 'pending']);
        }

        return 'endorsed';
    }

    /**
     * Reject the current stage.
     */
    public function reject($remarks = null)
    {
        // Update current stage to disapproved
        $this->update([
            'status' => 'disapproved',
            'remarks' => $remarks,
        ]);

        // Mark DTR adjustment as disapproved
        $this->dtrAdjustment->update(['status' => 'disapproved']);

        return 'disapproved';
    }

    /**
     * Determine if this stage is the next stage to approve.
     */
    public function getIsNextAttribute()
    {
        $nextStage = $this->dtrAdjustment->approvalStages()
            ->where('sequence', '>', $this->sequence)
            ->orderBy('sequence')
            ->first();

        return $nextStage && $nextStage->id === $this->id;
    }

    /**
     * Scopes
     */

    /**
     * Scope to filter pending stages.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to filter approved stages.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to filter rejected stages.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Helpers
     */

    /**
     * Check if the current stage is for a given employee.
     */
    public function isForEmployee($employeeId)
    {
        return $this->employee_id === $employeeId;
    }
}
