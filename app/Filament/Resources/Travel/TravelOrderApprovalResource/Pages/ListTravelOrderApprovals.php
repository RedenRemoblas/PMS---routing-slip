<?php

namespace App\Filament\Resources\Travel\TravelOrderApprovalResource\Pages;

use App\Filament\Resources\Travel\TravelOrderApprovalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTravelOrderApprovals extends ListRecords
{
    protected static string $resource = TravelOrderApprovalResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        return 'Travel Orders For Review';
    }
}
