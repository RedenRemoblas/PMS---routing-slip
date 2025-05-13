<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Hr\CocApprovalStage;
use Illuminate\Auth\Access\HandlesAuthorization;

class CocApprovalStagePolicy
{
    use HandlesAuthorization;



    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return ($user->hasRole('admin') &&  $user->employee !== null)  || $user->employee !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CocApprovalStage $model): bool
    {
        return ($user->hasRole('admin') &&  $user->employee !== null)  || ($user->employee && $user->employee->id === $model->employee_id);
    }

    /**
     * Determine whether the user can create CocApprovalStages.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the CocApprovalStage.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Hr\CocApprovalStage  $cocApprovalStage
     * @return mixed
     */
    public function update(User $user, CocApprovalStage $cocApprovalStage)
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the CocApprovalStage.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Hr\CocApprovalStage  $cocApprovalStage
     * @return mixed
     */
    public function delete(User $user, CocApprovalStage $cocApprovalStage)
    {
        return $user->hasRole('admin');
    }
}
