<?php

namespace App\Filament\Resources\Hr\LeaveAccrualResource\Pages;

use Filament\Actions;
use App\Models\Hr\LeaveAccrual;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Hr\LeaveAccrualResource;

class ListLeaveAccruals extends ListRecords
{
    protected static string $resource = LeaveAccrualResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add Leave Accrual') // Add a custom label for clarity

        ];
    }
}
