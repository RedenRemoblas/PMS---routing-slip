<?php

namespace App\Filament\Resources\Travel\TravelOrderResource\Pages;

use App\Filament\Resources\Travel\TravelOrderResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Actions\DeleteAction;
use Filament\Pages\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class ViewTravelOrder extends ViewRecord
{
    protected static string $resource = TravelOrderResource::class;

    public function getSubheading(): ?string
    {
        return __('Created by ') . $this->record->employee->full_name;
    }

    public function getTitle(): string|Htmlable
    {
        return __('Travel Order No.') . $this->record->id;
    }

    protected function getActions(): array
    {
        $user = Auth::user();
        $currentApprovalStage = $this->record->currentApprovalStageForUser($user->employee->id);
        $isCurrentApprover = $currentApprovalStage !== null;

        return [
            EditAction::make()
                ->label('Edit')
                ->url(fn() => $this->getResource()::getUrl('edit', ['record' => $this->record->getKey()]))
                ->after(fn() => redirect($this->getResource()::getUrl('view', ['record' => $this->record->getKey()])))
                ->hidden(fn() => $this->record->status !== 'Pending' || !Auth::user()->can('edit', $this->record)),

            DeleteAction::make()
                ->label('Delete')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->delete();
                    $this->sendNotification('Travel Order Deleted', 'The travel order has been successfully deleted.', 'success');
                    return $this->redirect(TravelOrderResource::getUrl('index'));
                })
                ->hidden(fn() => $this->record->status !== 'Pending' || !Auth::user()->can('edit', $this->record)),

            Action::make('lock')
                ->label('Lock')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    if ($this->record->lockTravelOrder()) {
                        $this->sendNotification('Travel Order Locked', 'Travel order locked successfully.', 'success');
                    } else {
                        $this->sendNotification('Lock Failed', 'Failed to lock the travel order. Check if a default approver exists.', 'danger');
                    }
                    $this->record->refresh();
                })
                ->hidden(fn() => $this->record->status !== 'Pending' || !Auth::user()->can('edit', $this->record)),

            Action::make('view_pdf')
                ->label('View PDF')
                ->url(fn() => route('travel-order.pdf', ['travelOrder' => $this->record->getKey()]))
                ->openUrlInNewTab()
                ->hidden(fn() => !in_array($this->record->status, ['Approved', 'Completed'])),
                
            Action::make('generate_pdf')
                ->label('Generate PDF')
                ->color('success')
                ->icon('heroicon-o-document-arrow-down')
                ->url(function () {
                    $hash = hash_hmac('sha256', $this->record->id, config('app.key'));
                    return route('travel.order.download', ['travelOrder' => $this->record->id, 'hash' => $hash]);
                })
                ->openUrlInNewTab()
                ->visible(fn() => in_array($this->record->status, ['Approved', 'Completed'])),

            Action::make('complete')
                ->label('Mark as Completed')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => 'Completed']);
                    $this->sendNotification('Travel Order Completed', 'The travel order has been marked as completed.', 'success');
                    $this->record->refresh();
                })
                ->hidden(fn() => $this->record->status !== 'Approved'),
        ];
    }

    private function sendNotification(string $title, string $body, string $type): void
    {
        Notification::make()
            ->title($title)
            ->body($body)
            ->{$type}()
            ->send();
    }
}
