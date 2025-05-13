<?php

namespace App\Policies;

use App\Models\Hr\Leave;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LeavePolicy
{
    use HandlesAuthorization;


    public function viewAny(User $user): bool
    {
        return $user->employee !== null;
    }

    public function view(User $user, Leave $model): bool
    {

        // Check if the user is an admin
        if ($user->hasRole('admin')) {
            return true;
        }
        // Check if the user is the owner
        if ($user->employee->id === $model->employee_id) {
            return true;
        }

        // Default return statement
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {

        // Check if the user is an admin
        if ($user->hasRole('admin')) {
            return true;
        }
        return $user->employee !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Leave $model): bool
    {
        return ($user->hasRole('admin') &&  $user->employee !== null);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Leave $model): bool
    {
        return  $user->employee !== null;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Leave $model): bool
    {
        return ($user->hasRole('admin') &&  $user->employee !== null);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Leave $model): bool
    {
        return ($user->hasRole('admin') &&  $user->employee !== null);
    }
}
