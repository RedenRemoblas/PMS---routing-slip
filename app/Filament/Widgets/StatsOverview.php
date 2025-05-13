<?php
// app/Filament/Widgets/StatsOverview.php
namespace App\Filament\Widgets;

use App\Models\User;

use App\Models\Employee;
use App\Models\Hr\Leave;
use App\Models\Travel\TravelOrder;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class StatsOverview extends BaseWidget
{
    protected function getCards(): array
    {
        return [

            Stat::make('Active Users',  User::where('is_active', true)->count()),
            Stat::make('Active Employees',  Employee::where('is_active', true)->count()),
            Stat::make(
                'Employees on Leave Today',
                Leave::whereHas('leaveDetails', function ($query) {
                    $query->whereDate('leave_date', today());
                })->distinct('employee_id')->count()
            ),
            Stat::make(
                'Employees on Travel Today',
                TravelOrder::where('status', 'approved') // Ensure only approved travel orders are considered
                    ->whereDate('inclusive_start_date', '<=', today())
                    ->whereDate('inclusive_end_date', '>=', today())
                    ->whereHas('details', function ($query) {
                        $query->distinct('employee_id');
                    })
                    ->count()
            ),


        ];
    }
}
