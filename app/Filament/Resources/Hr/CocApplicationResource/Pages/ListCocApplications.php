<?php

namespace App\Filament\Resources\Hr\CocApplicationResource\Pages;

use App\Filament\Resources\Hr\CocApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCocApplications extends ListRecords
{
    protected static string $resource = CocApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Create Coc')
                ->color('success')
                ->icon('heroicon-o-plus'),
        ];
    }
}
