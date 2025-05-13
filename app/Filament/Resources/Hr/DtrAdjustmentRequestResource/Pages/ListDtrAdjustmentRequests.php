<?php

namespace App\Filament\Resources\Hr\DtrAdjustmentRequestResource\Pages;

use App\Filament\Resources\Hr\DtrAdjustmentRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDtrAdjustmentRequests extends ListRecords
{
    protected static string $resource = DtrAdjustmentRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
