<?php

namespace App\Filament\Resources\Travel\TravelOrderResource\Pages;

use App\Filament\Resources\Travel\TravelOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTravelOrder extends EditRecord
{
    protected static string $resource = TravelOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn ($record) => $record->employee_id === auth()->user()->employee->id),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record->getKey()]);
    }
}
