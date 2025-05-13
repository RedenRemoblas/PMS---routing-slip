<?php

namespace App\Filament\Resources\Hr\ReviewCocApplicationResource\Pages;

use App\Filament\Resources\Hr\ReviewCocApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReviewCocApplications extends ListRecords
{
    protected static string $resource = ReviewCocApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //        Actions\CreateAction::make(),
        ];
    }
}
