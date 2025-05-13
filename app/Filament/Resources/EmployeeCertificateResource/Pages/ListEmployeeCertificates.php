<?php

namespace App\Filament\Resources\EmployeeCertificateResource\Pages;

use App\Filament\Resources\EmployeeCertificateResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListEmployeeCertificates extends ListRecords
{
    protected static string $resource = EmployeeCertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    

}
