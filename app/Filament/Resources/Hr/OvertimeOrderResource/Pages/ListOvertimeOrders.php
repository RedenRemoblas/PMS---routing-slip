<?php

namespace App\Filament\Resources\Hr\OvertimeOrderResource\Pages;

use App\Filament\Resources\Hr\OvertimeOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOvertimeOrders extends ListRecords
{
    protected static string $resource = OvertimeOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
