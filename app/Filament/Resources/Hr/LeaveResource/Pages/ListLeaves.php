<?php

namespace App\Filament\Resources\Hr\LeaveResource\Pages;

use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Hr\LeaveResource;
use Filament\Actions;
use Illuminate\Support\Facades\Auth;


class ListLeaves extends ListRecords
{
    protected static string $resource = LeaveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Create Leave')
                ->color('success')
                ->icon('heroicon-o-plus'),
        ];
    }
    

    /**
     * Customize the page title.
     */
    public function getTitle(): string
    {
        return 'My Leaves';
    }
    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        // Get the currently authenticated user
        $user = Auth::user();

        // Filter the records to only show those belonging to the user's employee
        return parent::getTableQuery()->where('employee_id', $user->employee->id);
    }
}
