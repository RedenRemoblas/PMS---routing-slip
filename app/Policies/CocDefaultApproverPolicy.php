<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Hr\CocDefaultApprover;
use Illuminate\Auth\Access\HandlesAuthorization;

class CocDefaultApproverPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any instances.
     */
    public function viewAny(User $user)
    {
        return ($user->hasAnyRole(['admin', 'hr-admin', 'leave-approver']) &&  $user->employee !== null) ;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CocDefaultApprover $cocDefaultApprover)
    {
        return ($user->hasAnyRole(['admin', 'hr-admin', 'leave-approver']) &&  $user->employee !== null);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user)
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CocDefaultApprover $cocDefaultApprover)
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CocDefaultApprover $cocDefaultApprover)
    {
        return $user->hasRole('admin');
    }
}
