<?php
namespace App\Policies;

use App\Models\Travel\TravelOrderDetail;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TravelOrderDetailPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any travel order details.
     */
    public function viewAny(User $user)
    {
        // Restrict access to only specific roles, e.g., 'admin' or 'internal'
        return $user->hasAnyRole(['admin']);
    }

    /**
     * Determine whether the user can view the travel order detail.
     */
    public function view(User $user, TravelOrderDetail $travelOrderDetail)
    {
        // Restrict access to only specific roles, e.g., 'admin' or 'internal'
        return $user->hasAnyRole(['admin']);
    }

    /**
     * Determine whether the user can create travel order details.
     */
    public function create(User $user)
    {
        // Restrict access to only specific roles, e.g., 'admin' or 'internal'
        return $user->hasAnyRole(['admin']);
    }

    /**
     * Determine whether the user can update the travel order detail.
     */
    public function update(User $user, TravelOrderDetail $travelOrderDetail)
    {
        // Restrict access to only specific roles, e.g., 'admin' or 'internal'
        return $user->hasAnyRole(['admin']);
    }

    /**
     * Determine whether the user can delete the travel order detail.
     */
    public function delete(User $user, TravelOrderDetail $travelOrderDetail)
    {
        // Restrict access to only specific roles, e.g., 'admin' or 'internal'
        return $user->hasAnyRole(['admin']);
    }
}
