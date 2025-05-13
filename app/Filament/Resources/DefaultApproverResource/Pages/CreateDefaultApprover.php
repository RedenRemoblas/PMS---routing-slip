<?php

namespace App\Filament\Resources\DefaultApproverResource\Pages;

use App\Filament\Resources\DefaultApproverResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDefaultApprover extends CreateRecord
{
    protected static string $resource = DefaultApproverResource::class;
    protected ?string $subheading = 'Approver for the Travel Order Module';


    protected function getRedirectUrl(): string
    {
        // Redirect to the list view after creating a new record
        return $this->getResource()::getUrl('index');
    }
}
