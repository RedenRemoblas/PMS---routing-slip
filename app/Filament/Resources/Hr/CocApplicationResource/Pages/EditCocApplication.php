<?php

namespace App\Filament\Resources\Hr\CocApplicationResource\Pages;

use App\Filament\Resources\Hr\CocApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCocApplication extends EditRecord
{
    protected static string $resource = CocApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record->getKey()]);
    }
}
