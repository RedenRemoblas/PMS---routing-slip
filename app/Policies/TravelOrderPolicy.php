<?php

namespace App\Policies;

use App\Models\Travel\TravelOrder;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TravelOrderPolicy
{
    /**
     * Determine whether the user can view any models.
     */

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TravelOrder $travelOrder)
    {

        // Check if the user is included in the TravelOrderDetails
        return ($travelOrder->approvalStages()->where('employee_id', $user->employee->id)->exists()
            || ($user->employee->id === $travelOrder->employee_id));
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Implement your logic here
        return true; // or false based on your logic
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TravelOrder $travelOrder): bool
    {
        return $user->employee->id === $travelOrder->employee_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TravelOrder $travelOrder): bool
    {
        return $user->employee->id === $travelOrder->employee_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TravelOrder $travelOrder): bool
    {
        return $user->employee->id === $travelOrder->employee_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TravelOrder $travelOrder): bool
    {
        return $user->employee->id === $travelOrder->employee_id;
    }

    /**
     * Determine whether the user can edit the model.
     */
    public function edit(User $user, TravelOrder $travelOrder): bool
    {
        return $user->employee->id === $travelOrder->employee_id;
    }
}
