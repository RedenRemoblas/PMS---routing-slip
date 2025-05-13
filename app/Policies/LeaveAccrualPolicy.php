<?php

namespace App\Policies;

use App\Models\Hr\LeaveAccrual;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Log;


class LeaveAccrualPolicy
{
    
   public function viewAny(User $user): bool
    {

        Log::info('LeaveAccrualPolicy viewany' . $user);

        return ($user->employee != null);
    }
  

    public function view(User $user, LeaveAccrual $model): bool
    {
        return ($user->employee != null);
  
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('leave-admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, LeaveAccrual $model): bool
    {
        return $user->hasRole('leave-admin');
  
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LeaveAccrual $model): bool
    {
        return $user->hasRole('leave-admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, LeaveAccrual $model): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, LeaveAccrual $model): bool
    {
        return $user->hasRole('admin');
    }
  
}
