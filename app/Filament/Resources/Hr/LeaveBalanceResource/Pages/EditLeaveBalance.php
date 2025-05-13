<?php

namespace App\Filament\Resources\Hr\LeaveBalanceResource\Pages;

use App\Filament\Resources\Hr\LeaveBalanceResource;
use App\Models\Hr\LeaveBalance;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditLeaveBalance extends EditRecord
{
    protected static string $resource = LeaveBalanceResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->validateUniqueCombination($data);

        return $data;
    }

    private function validateUniqueCombination(array $data)
    {
        $existingLeaveBalance = LeaveBalance::where('employee_id', $data['employee_id'])
            ->where('leave_type_id', $data['leave_type_id'])
            ->where('id', '!=', $this->record->id)
            ->first();

        if ($existingLeaveBalance) {
            Notification::make()
                ->title('Error')
                ->body('The combination of employee and leave type must be unique.')
                ->danger()
                ->send();

            throw ValidationException::withMessages([
                'employee_id' => 'The combination of employee and leave type must be unique.',
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
