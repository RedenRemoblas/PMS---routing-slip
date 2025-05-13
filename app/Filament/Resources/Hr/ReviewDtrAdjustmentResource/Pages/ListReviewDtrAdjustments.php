<?php

namespace App\Filament\Resources\Hr\ReviewDtrAdjustmentResource\Pages;

use App\Filament\Resources\Hr\ReviewDtrAdjustmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReviewDtrAdjustments extends ListRecords
{
    protected static string $resource = ReviewDtrAdjustmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //     Actions\CreateAction::make(),
        ];
    }
}
