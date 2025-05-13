<?php

namespace App\Filament\Resources\Hr\LeaveResource\Pages;

use App\Models\Hr\Leave;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\Hr\LeaveResource;
use Illuminate\Validation\ValidationException;

class CreateLeave extends CreateRecord
{
    protected static string $resource = LeaveResource::class;

    protected function beforeCreate(): void
    {
        // Retrieve leave details from the form data
        $leaveDetails = $this->data['leaveDetails'] ?? [];

        // Check if there are no leave details
        if (count($leaveDetails) === 0) {
            Notification::make()
                ->title('Validation Error')
                ->body('Leave must have at least one leave detail.')
                ->danger()
                ->send();

            throw ValidationException::withMessages([
                'leaveDetails' => 'Leave must have at least one leave detail.',
            ]);
        }

        // Check if there is enough leave balance
        $employeeId = $this->data['employee_id'];
        $leaveTypeId = $this->data['leave_type_id'];
        $totalDaysRequested = collect($leaveDetails)->sum('qty');

        try {
            Leave::checkLeaveBalance($employeeId, $leaveTypeId, $totalDaysRequested);
        } catch (ValidationException $e) {
            Notification::make()
                ->title('Insufficient Leave Balance')
                ->body('You do not have enough leave balance for the requested leave days.')
                ->danger()
                ->send();

            throw $e;
        }
    }

    protected function afterCreate(): void
    {
        // Retrieve leave details from the form data
       $leaveDetails = $this->data['leaveDetails'] ?? [];
    // Calculate the total days requested   ..no need for now since edit not allowed
  //  $totalDaysRequested = collect($leaveDetails)->sum('qty');

    // Update the total_days field in the Leave record
  //  $this->record->update(['total_days' => $totalDaysRequested]);

        // If the leave status is pending, update the days_reserved
        if ($this->record->leave_status === 'pending') {
            $employeeId = $this->data['employee_id'];
            $leaveTypeId = $this->data['leave_type_id'];
            $totalDaysReserved = collect($leaveDetails)->sum('qty');

            Leave::reserveLeaveDays($employeeId, $leaveTypeId, $totalDaysReserved);
        }
    }
}
