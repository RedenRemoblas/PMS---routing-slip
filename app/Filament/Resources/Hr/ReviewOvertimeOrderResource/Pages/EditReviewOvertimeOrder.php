<?php

namespace App\Filament\Resources\Hr\ReviewOvertimeOrderResource\Pages;

use Filament\Actions;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\Hr\ReviewOvertimeOrderResource;

class EditReviewOvertimeOrder extends EditRecord
{
    protected static string $resource = ReviewOvertimeOrderResource::class;

    public function getHeading(): string
    {
        return 'Overtime No: ' . $this->record->id . " [Status: " . $this->record->status . "]";
    }

    public function getSubHeading(): string
    {
        return "[ Created by: " . $this->record->creator->full_name . "]";
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
            $status = $currentStage->approve();
            Notification::make()
                ->title('Overtime Approved')
                ->body($status === 'approved' ? 'Overtime application has been fully approved.' : 'Overtime stage endorsed to the next approver.')
                ->success()
                ->send();
        } else {
            $currentStage->reject('Rejected by approver.');
            Notification::make()
                ->title('Overtime Disapproved')
                ->body('Overtime application has been disapproved.')
                ->danger()
                ->send();
        }

        $this->redirect($this->getResource()::getUrl('index'));
    }
}
