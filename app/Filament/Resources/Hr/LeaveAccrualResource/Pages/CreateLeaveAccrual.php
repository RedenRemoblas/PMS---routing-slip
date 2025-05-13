<?php

namespace App\Filament\Resources\Hr\LeaveAccrualResource\Pages;



use App\Models\Hr\LeaveAccrual;

use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\Hr\LeaveAccrualResource;

class CreateLeaveAccrual extends CreateRecord
{
    protected static string $resource = LeaveAccrualResource::class;

    //CreatePage.php
    protected function handleRecordCreation(array $data): LeaveAccrual
    {
        // Use the custom createAccrual method
        return LeaveAccrual::createAccrual($data);
    }
}
