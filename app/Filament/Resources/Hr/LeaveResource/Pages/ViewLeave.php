<?php

namespace App\Filament\Resources\Hr\LeaveResource\Pages;

use App\Models\Hr\Leave;
use Filament\Pages\Actions;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use App\Filament\Resources\Hr\LeaveResource;
use Illuminate\Validation\ValidationException;

class ViewLeave extends ViewRecord
{
    protected static string $resource = LeaveResource::class;

    public function getSubheading(): ?string
    {
        return __('Created by ') . $this->record->employee->full_name;
    }

    public function getTitle(): string|Htmlable
    {
        return __('Leave No.') . $this->record->id . ' [ ' . $this->record->leave_status . ']';
    }

    protected function getActions(): array
    {
        $actions = [
            Actions\DeleteAction::make()
                ->label('Delete')
                ->requiresConfirmation()
                ->modalHeading('Delete Leave')
                ->modalSubheading('Are you sure you want to delete this leave? This action cannot be undone.')
                ->modalButton('Yes, delete it')
                ->color('danger')
                ->action(function () {
                    $this->handleDeleteLeave($this->record);
                    $this->record->delete();

                    Notification::make()
                        ->title('Leave Deleted')
                        ->body('The leave has been successfully deleted.')
                        ->success()
                        ->send();

                    return $this->redirect(LeaveResource::getUrl('index'));
                })
                ->visible(fn(Leave $record) => ($record->leave_status === 'pending') && $record->employee_id === Auth::user()->employee->id),

            Actions\Action::make('lock')
                ->label('Lock')
                ->action(function () {
                    $this->record->lockLeave();
                    Notification::make()
                        ->title('Leave Locked')
                        ->body('Leave application has been locked and can no longer be edited.')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->color('warning')
                ->icon('heroicon-o-lock-closed')
                ->visible(fn(Leave $record) => $record->leave_status === 'pending' && $record->employee_id === Auth::user()->employee->id),

            Actions\Action::make('cancel')
                ->label('Cancel Leave')
                ->action(function () {
                    $this->handleCancelLeave($this->record);
                    Notification::make()
                        ->title('Leave Cancelled')
                        ->body('The leave application has been cancelled successfully.')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->visible(fn(Leave $record) => ($record->leave_status === 'locked') && $record->employee_id === Auth::user()->employee->id),
        ];

        // ✅ Show "View Approved Leave" **only if leave is approved/rejected** and has a valid file path
        if (in_array($this->record->leave_status, ['approved', 'disapproved'])) {
            if ($this->record->uploaded_file_path && $this->uploadedFileExists($this->record)) {
                $actions[] = Actions\Action::make('viewApprovedLeave')
                    ->label('View Approved Application')
                    ->url(fn() => $this->getUploadedFileUrl($this->record))
                    ->openUrlInNewTab(true)
                    ->icon('heroicon-o-eye');
            }
        }

        // ✅ Show "View Application" **if leave is NOT pending and PDF exists**
        if ($this->record->leave_status !== 'pending' && $this->pdfExists($this->record)) {
            $actions[] = Actions\Action::make('viewPdf')
                ->label('View Application')
                ->url(fn() => $this->getPdfUrl($this->record))
                ->openUrlInNewTab(true)
                ->icon('heroicon-o-eye');
        }

        // ✅ Show "Generate PDF" **only if leave is locked and PDF does NOT exist**
        if ($this->record->leave_status === 'locked' && !$this->pdfExists($this->record)) {
            $actions[] = Actions\Action::make('downloadPdf')
                ->label('Generate PDF')
                ->url(fn() => route('leave.pdf', $this->record->id))
                ->openUrlInNewTab()
                ->icon('heroicon-o-arrow-down');
        }

        return $actions;
    }

    /**
     * ✅ Check if the PDF exists at the stored `application_file_path`
     */
    private function pdfExists($record): bool
    {
        if (!$record->application_file_path) {
            return false;
        }

        // Ensure correct storage path
        $filePath = "leave-applications/" . basename($record->application_file_path);

        // Log the file being checked
        Log::info("Checking for PDF file at: {$filePath}");

        return Storage::exists($filePath);
    }

    /**
     * ✅ Check if the uploaded file exists at `uploaded_file_path`
     */
    private function uploadedFileExists($record): bool
    {
        if (!$record->uploaded_file_path) {
            return false;
        }

        // Ensure correct storage path
        $filePath = "leave-uploads/" . basename($record->uploaded_file_path);

        // Log the file being checked
        Log::info("Checking for uploaded file at: {$filePath}");

        return Storage::exists($filePath);
    }

    /**
     * ✅ Generate the correct URL for the PDF stored in `application_file_path`
     */
    private function getPdfUrl($record): string
    {
        return Storage::url("leave-applications/" . basename($record->application_file_path));
    }

    /**
     * ✅ Generate the correct URL for the approved leave stored in `uploaded_file_path`
     */
    private function getUploadedFileUrl($record): string
    {
        return Storage::url("leave-uploads/" . basename($record->uploaded_file_path));
    }

    protected function handleDeleteLeave(Leave $leave): void
    {
        $employeeId = $leave->employee_id;
        $leaveTypeId = $leave->leave_type_id;
        $totalDaysReserved = $leave->leaveDetails()->sum('qty');

        try {
            Leave::deductReservedLeaveDays($employeeId, $leaveTypeId, $totalDaysReserved);
            Log::info("Successfully deducted {$totalDaysReserved} reserved days for employee ID {$employeeId}, leave type ID {$leaveTypeId}.");
        } catch (ValidationException $e) {
            Log::error("Error deducting reserved days for employee ID {$employeeId}, leave type ID {$leaveTypeId}: " . $e->getMessage());
            throw $e;
        }
    }

    protected function handleCancelLeave(Leave $leave): void
    {
        $employeeId = $leave->employee_id;
        $leaveTypeId = $leave->leave_type_id;
        $totalDaysReserved = $leave->leaveDetails()->sum('qty');

        try {
            Leave::deductReservedLeaveDays($employeeId, $leaveTypeId, $totalDaysReserved);
            $leave->update(['leave_status' => 'cancelled']);

            Log::info("Successfully cancelled leave for employee ID {$employeeId}, leave type ID {$leaveTypeId}. Reserved days deducted: {$totalDaysReserved}.");
        } catch (ValidationException $e) {
            Log::error("Error cancelling leave for employee ID {$employeeId}, leave type ID {$leaveTypeId}: " . $e->getMessage());
            throw $e;
        }
    }
}
