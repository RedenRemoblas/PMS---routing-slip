<?php

namespace App\Models\Hr;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvertimeApprovalStage extends Model
{
    use HasFactory;

    // Specify the table name if necessary
    protected $table = 'overtime_approval_stages';

    // Define the fillable properties
    protected $fillable = [
        'employee_id',
        'overtime_order_id',
        'status',
        'remarks',
        'sequence',
    ];

    // Define relationships

    /**
     * Get the employee associated with the overtime approval stage.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the overtime order associated with the approval stage.
     */
    public function overtimeOrder()
    {
        return $this->belongsTo(OvertimeOrder::class);
    }

    /**
     * Approve the current stage and move to the next stage.
     */
    public function approve()
    {
        // Update current stage to approved
        $this->update(['status' => 'approved']);

        $nextStage = $this->overtimeOrder->approvalStages()
            ->where('sequence', '>', $this->sequence)
            ->orderBy('sequence')
            ->first();

        if ($nextStage === null) {
            // All stages completed, mark overtime order as approved
            $this->overtimeOrder->update(['status' => 'approved']);
        } else {
            // Move to the next stage
            $nextStage->update(['status' => 'pending']);
        }

        return  'endorsed';
    }

    public function reject($remarks = null)
    {
        // Update current stage to disapproved
        $this->update([
            'status' => 'disapproved',
            'remarks' => $remarks,
        ]);

        // Mark overtime order as disapproved
        $this->overtimeOrder->update(['status' => 'disapproved']);

        return 'disapproved';
    }


    /**
     * Determine if this stage is the next stage to approve.
     */
    public function getIsNextAttribute()
    {
        $nextStage = $this->overtimeOrder->approvalStages()
            ->where('sequence', '>', $this->sequence)
            ->orderBy('sequence')
            ->first();

        return $nextStage && $nextStage->id === $this->id;
    }
}
