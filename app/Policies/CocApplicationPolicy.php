<?php

namespace App\Policies;

use App\Models\User;
use App\Models\DefaultApprover;
use App\Models\Hr\CocApplication;
use Illuminate\Auth\Access\HandlesAuthorization;

class CocApplicationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return   $user->employee !== null && $user->employee->employee_no !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CocApplication $cocApplication): bool
    {
        return ($user->hasAnyRole('admin') &&  $user->employee !== null) ||
            ($user->employee && $user->employee->id === $cocApplication->employee_id) ||
            $cocApplication->approvalStages()->where('employee_id', $user->employee->id)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        $employee = $user->employee;

        // Check if the user has an associated employee and the employee_no is set
        if ($employee && $employee->employee_no) {
            return true;
        }

        return false;
    }


    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CocApplication $cocApplication): bool
    {


        // Allow the creator to update the request
        if ($user->employee->id === $cocApplication->employee_id) {
            return true;
        }

        // Allow the approver to update the request if they are listed in the approval stages
        return $cocApplication->approvalStages()
            ->where('employee_id', $user->employee->id)
            ->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CocApplication $cocApplication): bool
    {
        return ($user->employee && $user->employee->id === $cocApplication->employee_id);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CocApplication $cocApplication): bool
    {
        return ($user->hasRole('admin') &&  $user->employee !== null) ||
            ($user->employee && $user->employee->id === $cocApplication->employee_id) ||
            $cocApplication->approvalStages()->where('employee_id', $user->employee->id)->exists();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CocApplication $cocApplication): bool
    {
        return ($user->hasRole('admin') &&  $user->employee !== null) ||
            ($user->employee && $user->employee->id === $cocApplication->employee_id) ||
            $cocApplication->approvalStages()->where('employee_id', $user->employee->id)->exists();
    }
}
