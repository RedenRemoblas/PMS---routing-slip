<?php

namespace App\Filament\Resources\Hr\DtrResource\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Hr\DtrResource;
use Carbon\Carbon;

class ListDtrs extends ListRecords
{
    protected static string $resource = DtrResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('show_monthly_report')
                ->label('Generate PDF')
                ->icon('heroicon-o-document')
                ->url(function () {
                    // Access the table's query state to get the applied filter value
                    $filters = $this->getTable()->getFilters();

                    // Extract the 'month' filter value, defaulting to the current month if not set
                    $month = $filters['month']->getState()['month'] ?? now()->format('Y-m');

                    return route('dtr.view.pdf', ['month' => $month]);
                })
                ->openUrlInNewTab(),

            /*  Action::make('view_dtr')
                ->label('View DTR')
                ->icon('heroicon-o-eye')
                ->url(function () {
                    // Access the table's query state to get the applied filter value
                    $filters = $this->getTable()->getFilters();

                    // Extract the 'month' filter value, defaulting to the current month if not set
                    $month = $filters['month']->getState()['month'] ?? now()->format('Y-m');

                    return route('dtr.view.dtr', ['month' => $month]);
                })
                ->openUrlInNewTab(), */
        ];
    }
}
