<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Employee;

class LeaveApprover extends Model
{
    protected $fillable = [
        'leave_id',
        'approver_id',
        'alternate_approver_id',
        'status',
        'remarks',
        'approved_at'
    ];

    protected $casts = [
        'approved_at' => 'datetime'
    ];

    public function leave(): BelongsTo
    {
        return $this->belongsTo(Leave::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approver_id');
    }

    public function alternateApprover(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'alternate_approver_id');
    }

    public function approve($remarks = null, $isAlternate = false)
    {
        $this->status = 'approved';
        $this->remarks = $remarks;
        $this->approved_at = now();
        $this->save();

        // Update leave approval status
        $leave = $this->leave;
        if ($isAlternate) {
            $leave->approval_level = 'alternate_approved';
        } else {
            if ($leave->requires_alternate_approval && $leave->approval_level !== 'alternate_approved') {
                return false; // Cannot approve if alternate approval is required but not yet given
            }
            $leave->approval_level = 'final_approved';
            $leave->leave_status = 'approved';
        }
        $leave->save();

        return true;
    }

    public function disapprove($remarks)
    {
        $this->status = 'disapproved';
        $this->remarks = $remarks;
        $this->approved_at = now();
        $this->save();

        // Update leave status
        $leave = $this->leave;
        $leave->leave_status = 'disapproved';
        $leave->save();

        return true;
    }
}
