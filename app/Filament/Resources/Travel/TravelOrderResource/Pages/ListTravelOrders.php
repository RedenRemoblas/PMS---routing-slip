<?php

namespace App\Filament\Resources\Travel\TravelOrderResource\Pages;

use App\Filament\Resources\Travel\TravelOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTravelOrders extends ListRecords
{
    protected static string $resource = TravelOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
