<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Setup\Holiday;
use Illuminate\Auth\Access\HandlesAuthorization;

class HolidayPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the
     *
     *
     *  user can create holidays.
     */


    /**
     * Determine whether the user can view the model.
     */
    public function viewAny(User $user): bool
    {
        return ($user->hasAnyRole(['admin', 'hr-admin', 'leave-approver']) );
    }

    public function view(User $user, NewUser $newUser)
    {
        // Can view if admin or if the new user is the current user
        return $user->hasAnyRole(['admin', 'hr-admin', 'leave-admin']);
    }
    public function create(User $user)
    {
        // Example: Only admins can create holidays
        return $user->hasAnyRole(['admin', 'hr-admin', 'leave-admin']);
    }

    /**
     * Determine whether the user can update the holiday.
     */
    public function update(User $user, Holiday $holiday)
    {
        // Example: Only admins can update holidays
        return $user->hasAnyRole(['admin', 'hr-admin', 'leave-admin']);
    }

    /**
     * Determine whether the user can delete the holiday.
     */
    public function delete(User $user, Holiday $holiday)
    {
        // Example: Only admins can delete holidays
        return $user->hasAnyRole(['admin', 'hr-admin', 'leave-admin']);
    }
}
