<?php

namespace App\Policies;

use App\Models\Hr\DtrAdjustmentRequest;
use App\Models\User;

class DtrAdjustmentRequestPolicy
{
    /**
     * Determine whether the user can view any DtrAdjustmentRequest.
     */
    public function viewAny(User $user): bool
    {
        // Allow all users to view any DtrAdjustmentRequest
        return true;
    }

    /**
     * Determine whether the user can view a specific DtrAdjustmentRequest.
     */
    public function view(User $user, DtrAdjustmentRequest $dtrAdjustmentRequest): bool
    {
        // Allow all users to view a specific DtrAdjustmentRequest
        return true;
    }

    /**
     * Determine whether the user can edit a specific DtrAdjustmentRequest.
     */
    public function edit(User $user, DtrAdjustmentRequest $dtrAdjustmentRequest): bool
    {
        // Allow all users to edit any DtrAdjustmentRequest
        return true;
    }

    /**
     * Determine whether the user can update the DTR adjustment request.
     */
    public function update(User $user, DtrAdjustmentRequest $dtrAdjustmentRequest): bool
    {
        // Allow the creator to update the request
        if ($user->employee->id === $dtrAdjustmentRequest->created_by) {
            return true;
        }

        // Allow the approver to update the request if they are listed in the approval stages
        return $dtrAdjustmentRequest->approvalStages()
            ->where('employee_id', $user->employee->id)
            ->exists();
    }
}
