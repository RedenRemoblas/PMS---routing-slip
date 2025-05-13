<?php

namespace App\Filament\Resources\Travel\TravelApprovalStageResource\Pages;

use App\Filament\Resources\Travel\TravelApprovalStageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTravelApprovalStages extends ListRecords
{
    protected static string $resource = TravelApprovalStageResource::class;

    protected function getHeaderActions(): array
    {
        return [
      //      Actions\CreateAction::make(),
        ];
    }
}
