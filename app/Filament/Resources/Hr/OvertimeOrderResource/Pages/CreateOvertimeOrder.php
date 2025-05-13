<?php

namespace App\Filament\Resources\Hr\OvertimeOrderResource\Pages;

use Filament\Actions;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\Hr\OvertimeOrderResource;

class CreateOvertimeOrder extends CreateRecord
{
    protected static string $resource = OvertimeOrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id(); // Set the current user as the default value for created_by
        return $data;
    }
}
