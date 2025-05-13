<?php

namespace App\Filament\Resources\Hr\DtrResource\Pages;

use App\Filament\Resources\Hr\DtrResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDtr extends EditRecord
{
    protected static string $resource = DtrResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
