<?php

namespace App\Models\Travel;

use App\Models\Employee;
use App\Models\Setup\Project;
use App\Models\DefaultApprover;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use App\Models\Setup\Division;

class TravelOrder extends Model
{
    protected $fillable = [
        'employee_id',
        'inclusive_start_date',
        'inclusive_end_date',
        'funding_type',
        'official_vehicle',
        'place_of_origin',
        'destination',
        'status',
        'farthest_distance',
        'purpose',
        'division_id',
        'date_approved',
    ];

    public function details()
    {
        return $this->hasMany(TravelOrderDetail::class);
    }

    public function approvalStages()
    {
        return $this->hasMany(TravelApprovalStage::class);
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Determine if the travel order has default approvers
     * for the division with the most employees.
     */
    public function hasDefaultApprovers()
    {
        $divisionId = $this->getDivisionWithMostEmployees();

        if (!$divisionId) {
            Log::warning('No division found for default approvers', ['travel_order' => $this]);
            return false;
        }

        return DefaultApprover::where('division_id', $divisionId)->exists();
    }

    /**
     * Get the current approval stage for the travel order.
     */
    public function currentApprovalStage()
    {
        return $this->approvalStages()
            ->where('status', 'pending')
            ->orderBy('sequence')
            ->first();
    }

    /**
     * Get the current approval stage for a specific user.
     */
    public function currentApprovalStageForUser($userId)
    {
        return $this->approvalStages()
            ->where('employee_id', $userId)
            ->where('status', 'pending')
            ->orderBy('sequence')
            ->first();
    }

    /**
     * Lock the travel order and assign default approvers.
     */
    public function lockTravelOrder()
    {
        if ($this->status === 'Locked') {
            Log::warning('Travel order already locked', ['travel_order' => $this]);
            return false;
        }

        // Check if there are default approvers for the determined division
        if (!$this->hasDefaultApprovers()) {
            Log::warning('No default approvers found for the division', ['travel_order' => $this]);
            return false;
        }

        // Update the status to 'locked'
        $this->update(['status' => 'Locked']);

        // Assign approvers after locking
        $this->assignApprovers();

        Log::info('Travel order locked and approvers assigned', ['travel_order' => $this]);
        return true;
    }

    /**
     * Assign approvers to the travel order based on the division with the most employees.
     */
    public function assignApprovers()
    {
        Log::info('Assigning approvers', ['travel_order' => $this]);

        $divisionId = $this->getDivisionWithMostEmployees();

        if (!$divisionId) {
            Log::warning('No division found for assigning approvers', ['travel_order' => $this]);
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

        // Assign statuses based on the approver sequence
        foreach ($defaultApprovers as $index => $defaultApprover) {
            //     $status = ($index === $defaultApprovers->count() - 1) ? 'approved' : 'endorsed';

            $this->approvalStages()->create([
                'employee_id' => $defaultApprover->employee_id,
                'status' => 'pending',
                'division_id' => $defaultApprover->division_id,
                'sequence' => $defaultApprover->sequence,
            ]);
        }

        Log::info('Approvers assigned from division', ['division_id' => $divisionId]);
    }

    /**
     * Helper method to get the division with the most employees in the travel order.
     */
    protected function getDivisionWithMostEmployees()
    {
        $divisionCounts = $this->details()
            ->with(['employee', 'employee.division']) // Eager load employee and division
            ->get()
            ->groupBy('employee.division_id')
            ->map(fn($group) => $group->count())
            ->sortDesc();

        return $divisionCounts->keys()->first();
    }
}
