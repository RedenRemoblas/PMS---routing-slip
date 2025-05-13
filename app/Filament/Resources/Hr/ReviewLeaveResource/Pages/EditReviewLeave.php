<?php

namespace App\Filament\Resources\Hr\ReviewLeaveResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\Hr\ReviewLeaveResource;
use Illuminate\Support\Facades\Storage;

class EditReviewLeave extends EditRecord
{
    protected static string $resource = ReviewLeaveResource::class;

    public function getHeading(): string
    {
        return 'Leave No: ' . ($this->record->id) . " [Status: " . $this->record->leave_status . "]";
    }

    public function getSubHeading(): string
    {
        return "[" . $this->record->employee->full_name . "][" . $this->record->leaveType->leave_name . "]";
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label('Approve')
                ->action(function () {
                    $this->saveForm(); // Save changes first
                    if (!$this->record->uploaded_file_path) {
                        Notification::make()
                            ->title('File Missing')
                            ->body('Please upload the required file before approving.')
                            ->danger()
                            ->send();
                        return;
                    }
                    $this->handleApproval(true);
                })
                ->requiresConfirmation()
                ->color('success'),

            Actions\Action::make('reject')
                ->label('Disapprove')
                ->action(function () {
                    $this->saveForm(); // Save changes first
                    if (!$this->record->uploaded_file_path) {
                        Notification::make()
                            ->title('File Missing')
                            ->body('Please upload the required file before disapproving.')
                            ->danger()
                            ->send();
                        return;
                    }
                    $this->handleApproval(false);
                })
                ->requiresConfirmation()
                ->color('danger')
                ->icon('heroicon-o-x-circle'),

            Actions\Action::make('cancel')
                ->label('Cancel')
                ->action(function () {
                    $this->redirect($this->getResource()::getUrl('index'));
                })
                ->color('secondary')
                ->icon('heroicon-o-arrow-left'),
        ];
    }

    protected function saveForm(): void
    {
        $data = $this->form->getState();

        if (empty($data)) {
            Notification::make()
                ->title('Form Incomplete')
                ->body('Please ensure all required fields are filled in before proceeding.')
                ->danger()
                ->send();

            return;
        }

        // ✅ Ensure uploaded file path is updated
        if (isset($data['uploaded_file_path'])) {
            $filePath = $data['uploaded_file_path'];
            $this->record->update(['uploaded_file_path' => Storage::url($filePath)]);

            Notification::make()
                ->title('File Uploaded')
                ->body('Supporting document has been uploaded successfully.')
                ->success()
                ->send();
        }

        $this->record->fill($data);
        $this->record->save();

        Notification::make()
            ->title('Changes Saved')
            ->body('Your changes have been saved successfully.')
            ->success()
            ->send();
    }

    protected function handleApproval(bool $isApproved): void
    {
        if ($isApproved) {
            // Delegate approval logic to the model
            $this->record->approveLeave();

            Notification::make()
                ->title('Leave Approved')
                ->body('Leave application has been approved.')
                ->success()
                ->send();
        } else {
            $this->record->update(['leave_status' => 'disapproved']);

            Notification::make()
                ->title('Leave Disapproved')
                ->body('Leave application has been disapproved.')
                ->danger()
                ->send();
        }

        $this->redirect($this->getResource()::getUrl('index'));
    }
}
