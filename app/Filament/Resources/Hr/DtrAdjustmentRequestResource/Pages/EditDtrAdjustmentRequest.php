<?php

namespace App\Filament\Resources\Hr\DtrAdjustmentRequestResource\Pages;

use App\Filament\Resources\Hr\DtrAdjustmentRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDtrAdjustmentRequest extends EditRecord
{
    protected static string $resource = DtrAdjustmentRequestResource::class;

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
