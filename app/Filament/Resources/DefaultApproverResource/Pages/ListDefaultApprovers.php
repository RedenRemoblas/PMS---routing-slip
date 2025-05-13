<?php

namespace App\Filament\Resources\DefaultApproverResource\Pages;

use App\Filament\Resources\DefaultApproverResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDefaultApprovers extends ListRecords
{
    protected static string $resource = DefaultApproverResource::class;

    protected ?string $subheading = 'List of Approvers for the all transactions that requires approval.';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
