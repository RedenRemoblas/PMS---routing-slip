<?php

namespace App\Filament\Resources\Hr\ReviewOvertimeOrderResource\Pages;

use App\Filament\Resources\Hr\ReviewOvertimeOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReviewOvertimeOrders extends ListRecords
{
    protected static string $resource = ReviewOvertimeOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //  Actions\CreateAction::make(),
        ];
    }
}
