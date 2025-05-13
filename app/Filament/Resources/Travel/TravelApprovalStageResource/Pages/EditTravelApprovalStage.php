<?php

namespace App\Filament\Resources\Travel\TravelApprovalStageResource\Pages;

use App\Filament\Resources\Travel\TravelApprovalStageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTravelApprovalStage extends EditRecord
{
    protected static string $resource = TravelApprovalStageResource::class;

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
