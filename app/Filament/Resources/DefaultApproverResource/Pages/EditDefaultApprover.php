<?php

namespace App\Filament\Resources\DefaultApproverResource\Pages;

use App\Filament\Resources\DefaultApproverResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDefaultApprover extends EditRecord
{
    protected static string $resource = DefaultApproverResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
