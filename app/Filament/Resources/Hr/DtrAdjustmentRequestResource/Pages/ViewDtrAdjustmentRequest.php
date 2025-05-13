<?php

namespace App\Filament\Resources\Hr\DtrAdjustmentRequestResource\Pages;


use App\Filament\Resources\Hr\DtrAdjustmentRequestResource;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Actions\Action;
use Filament\Pages\Actions\EditAction;
use Filament\Pages\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewDtrAdjustmentRequest extends ViewRecord
{
    protected static string $resource = DtrAdjustmentRequestResource::class;

    protected function getActions(): array
    {
        return [
            EditAction::make()
                ->label('Edit')
                ->url(fn() => $this->getResource()::getUrl('edit', ['record' => $this->record->getKey()]))
                ->hidden(fn() => strtolower($this->record->status) !== 'pending' || !Auth::user()->can('update', $this->record)),

            DeleteAction::make()
                ->label('Delete')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->delete();
                    Notification::make()
                        ->title('DTR Adjustment Request Deleted')
                        ->body('The DTR adjustment request has been successfully deleted.')
                        ->success()
                        ->send();

                    return $this->redirect(DtrAdjustmentRequestResource::getUrl('index'));
                })
                ->hidden(fn() => strtolower($this->record->status) !== 'pending' || !Auth::user()->can('delete', $this->record)),

            Action::make('lock')
                ->label('Lock')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    if ($this->record->lockRequest()) {
                        Notification::make()
                            ->title('DTR Adjustment Request Locked')
                            ->body('DTR adjustment request locked successfully.')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Lock Failed')
                            ->body('Failed to lock the DTR adjustment request. Ensure all requirements are met.')
                            ->danger()
                            ->send();
                    }
                    $this->record->refresh();
                })
                ->hidden(fn() => strtolower($this->record->status) !== 'pending' || !Auth::user()->can('update', $this->record)),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index', ['record' => $this->record->getKey()]);
    }
}
