<?php

namespace App\Filament\Resources\Hr\ReviewDtrAdjustmentResource\Pages;

use Filament\Actions;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\Hr\ReviewDtrAdjustmentResource;

class EditReviewDtrAdjustment extends EditRecord
{
    protected static string $resource = ReviewDtrAdjustmentResource::class;

    public function mount($record): void
    {
        parent::mount($record);


        // Check policy
        if (!Auth::user()->can('edit', $this->record)) {
            Log::warning('Unauthorized Access Attempt', ['user_id' => Auth::id(), 'record_id' => $record]);
            abort(403, 'You are not authorized to review this DTR adjustment.');
        }
    }


    public function getHeading(): string
    {
        return 'DTR Adjustment No: ' . $this->record->id . " [Status: " . $this->record->status . "]";
    }

    public function getSubHeading(): string
    {
        return "[ Submitted by: " . $this->record->employee->full_name . "]";
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label('Approve')
                ->action(fn() => $this->handleApproval(true))
                ->visible(fn() => $this->record->currentApprovalStageForUser(Auth::id()) !== null)
                ->requiresConfirmation()
                ->color('success'),

            Actions\Action::make('reject')
                ->label('Disapprove')
                ->action(fn() => $this->handleApproval(false))
                ->visible(fn() => $this->record->currentApprovalStageForUser(Auth::id()) !== null)
                ->requiresConfirmation()
                ->color('danger')
                ->icon('heroicon-o-x-circle'),

            Actions\Action::make('cancel')
                ->label('Cancel')
                ->action(fn() => $this->redirect($this->getResource()::getUrl('index')))
                ->color('primary')
                ->icon('heroicon-o-arrow-left'),
        ];
    }

    protected function handleApproval(bool $isApproved): void
    {
        $currentStage = $this->record->approvalStages()
            ->where('employee_id', Auth::id())
            ->where('status', 'pending')
            ->first();

        if (!$currentStage) {
            Notification::make()
                ->title('Approval Not Allowed')
                ->body('You are not authorized to approve or reject this stage.')
                ->danger()
                ->send();
            return;
        }

        if ($isApproved) {
            // Approve the current stage
            $currentStage->update(['status' => 'approved']);

            // Check if there are more stages
            $nextStage = $this->record->approvalStages()
                ->where('sequence', '>', $currentStage->sequence)
                ->orderBy('sequence')
                ->first();

            if (!$nextStage) {
                // All stages completed, mark as approved and sync entries to DTR
                $this->record->update(['status' => 'Approved']);
                $this->record->syncApprovedEntriesToDtr();

                Notification::make()
                    ->title('DTR Adjustment Approved')
                    ->body('DTR adjustment has been fully approved, and entries have been synced to the DTR table.')
                    ->success()
                    ->send();
            } else {
                // Move to the next stage
                $nextStage->update(['status' => 'pending']);

                Notification::make()
                    ->title('Approval Endorsed')
                    ->body('DTR adjustment stage endorsed to the next approver.')
                    ->success()
                    ->send();
            }
        } else {
            // Reject the current stage
            $currentStage->update(['status' => 'rejected', 'remarks' => 'Rejected by approver.']);
            $this->record->update(['status' => 'Rejected']);

            Notification::make()
                ->title('DTR Adjustment Disapproved')
                ->body('DTR adjustment application has been disapproved.')
                ->danger()
                ->send();
        }

        $this->redirect($this->getResource()::getUrl('index'));
    }
}
