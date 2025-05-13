<?php

namespace App\Filament\Resources\Hr\CocApplicationResource\Pages;

use App\Filament\Resources\Hr\CocApplicationResource;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Actions\Action;
use Filament\Pages\Actions\EditAction;
use Filament\Pages\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewCocApplication extends ViewRecord
{
    protected static string $resource = CocApplicationResource::class;

    protected function getActions(): array
    {
        $actions = [
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
                        ->title('COC Application Deleted')
                        ->body('The COC application has been successfully deleted.')
                        ->success()
                        ->send();

                    return $this->redirect(CocApplicationResource::getUrl('index'));
                })
                ->hidden(fn() => strtolower($this->record->status) !== 'pending' || !Auth::user()->can('delete', $this->record)),

            Action::make('lock')
                ->label('Lock')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    if ($this->record->lock()) {
                        Notification::make()
                            ->title('COC Application Locked')
                            ->body('COC application locked successfully.')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Lock Failed')
                            ->body('Failed to lock the COC application. Ensure all requirements are met.')
                            ->danger()
                            ->send();
                    }
                    $this->record->refresh();
                })
                ->hidden(fn() => strtolower($this->record->status) !== 'pending' || !Auth::user()->can('update', $this->record)),
        ];

        return $actions;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index', ['record' => $this->record->getKey()]);
    }
}
