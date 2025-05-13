<?php

namespace App\Policies;

use App\Models\Hr\LeaveType;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LeaveTypePolicy
{
    public function viewAny(User $user): bool
    {

        return ($user->hasAnyRole(['admin', 'hr-admin']) &&  $user->employee !== null) ;
    }


    public function view(User $user, LeaveType $model): bool
    {
        return ($user->hasAnyRole(['admin', 'hr-admin']) &&  $user->employee !== null);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return ($user->hasAnyRole(['admin', 'hr-admin']) &&  $user->employee !== null) ;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, LeaveType $model): bool
    {
        return ($user->hasAnyRole(['admin', 'hr-admin']) &&  $user->employee !== null) ;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LeaveType $model): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, LeaveType $model): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, LeaveType $model): bool
    {
        return $user->hasRole('admin');
    }
}
