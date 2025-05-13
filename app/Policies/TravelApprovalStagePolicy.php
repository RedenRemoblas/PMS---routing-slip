<?php

namespace App\Policies;

use App\Models\Travel\TravelApprovalStage;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TravelApprovalStagePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // return ($user->hasRole('admin') &&  $user->employee !== null)  || $user->employee !== null;
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TravelApprovalStage $model): bool
    {
        //   return ($user->hasRole('to-admin') &&  $user->employee !== null)  || ($user->employee && $user->employee->id === $model->employee_id);
        return true;
    }


    /**
     * Determine whether the user can view the travel approval stage.
     */

    //each travel order has a travel approval stage. if the user employeee id is the same as the travel order employee id,
    // then the user can view the travel approval stage. also if the user is an admin, the user can view the travel approval stage.
    // all employees included in travel order details can view the travel approval stage.


    /**
     * Determine whether the user can create travel approval stages.
     */
    public function create(User $user)
    {

        $employee = $user->employee;
        return ($user->employee && $user->employee->id === $employee->employee_id);
    }

    /**
     * Determine whether the user can update the travel approval stage.
     */
    public function update(User $user, TravelApprovalStage $travelApprovalStage)
    {

        $employee = $user->employee;
        return ($user->employee && $user->employee->id === $employee->employee_id);
    }

    /**
     * Determine whether the user can delete the travel approval stage.
     */
    public function delete(User $user, TravelApprovalStage $travelApprovalStage)
    {
        $employee = $user->employee;
        return ($user->employee && $user->employee->id === $employee->employee_id);
    }

    /**
     * Determine whether the user can restore the travel approval stage.
     */
    public function restore(User $user, TravelApprovalStage $travelApprovalStage)
    {
        return ($user->hasRole('admin') &&  $user->employee !== null)  || ($user->employee && $user->employee->id === $model->employee_id);
    }

    /**
     * Determine whether the user can permanently delete the travel approval stage.
     */
    public function forceDelete(User $user, TravelApprovalStage $travelApprovalStage)
    {
        return $user->hasRole('admin');
    }
}
