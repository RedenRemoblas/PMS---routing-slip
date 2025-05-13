<?php

namespace App\Policies;

use App\Models\Hr\OvertimeOrder;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OvertimeOrderPolicy
{
    /**
     * Determine whether the user can view any models.
     */

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, OvertimeOrder $overtimeOrder)
    {
        // Check if the user is an admin
        if ($user->hasRole('admin')) {
            return true;
        }

        // Check if the user is the owner
        if ($user->employee->id === $overtimeOrder->created_by) {
            return true;
        }

        // Check if the user is included in the OvertimeOrderDetails
        return $overtimeOrder->details()->where('employee_id', $user->employee->id)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // Adjust logic as needed
    }

    public function update(User $user, OvertimeOrder $overtimeOrder): bool
    {
        // Allow the creator to update the request
        if ($user->employee->id === $overtimeOrder->created_by) {
            return true;
        }

        // Allow the approver to update the request if they are listed in the approval stages
        return $overtimeOrder->approvalStages()
            ->where('employee_id', $user->employee->id)
            ->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, OvertimeOrder $overtimeOrder): bool
    {
        return $user->employee->id === $overtimeOrder->created_by;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, OvertimeOrder $overtimeOrder): bool
    {
        return $user->employee->id === $overtimeOrder->created_by;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, OvertimeOrder $overtimeOrder): bool
    {
        return $user->employee->id === $overtimeOrder->created_by;
    }

    /**
     * Determine whether the user can edit the model.
     */
    public function edit(User $user, OvertimeOrder $overtimeOrder): bool
    {
        return $user->employee->id === $overtimeOrder->created_by;
    }
}
