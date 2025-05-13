<?php

namespace App\Filament\Resources\Hr\OvertimeOrderResource\Pages;

use App\Filament\Resources\Hr\OvertimeOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOvertimeOrder extends EditRecord
{
    protected static string $resource = OvertimeOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
