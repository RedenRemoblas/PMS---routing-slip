<?php


namespace App\Filament\Resources\Hr\DtrResource\Pages;

use Carbon\Carbon;
use App\Models\Hr\Dtr;
use App\Models\Hr\Leave;
use App\Models\Setup\Holiday;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\Hr\DtrResource;
use App\Models\Travel\TravelOrder; // Import the TravelOrder model
use App\Models\Travel\TravelOrderDetail; // Import the TravelOrderDetail model

class MonthlyDtrReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.resources.hr.dtr-resource.pages.monthly-dtr';
    protected static ?string $navigationGroup = 'HR';

    public function generateDtrPdf($month)
    {
        // Log the month passed to the method
        Log::info('Generating DTR PDF for Month:', ['month' => $month]);

        $employee = Auth::user()->employee;

        $dtrRecords = Dtr::where('employee_dtr_no', $employee->employee_no)
            ->whereMonth('dtr_timestamp', Carbon::parse($month)->month)
            ->whereYear('dtr_timestamp', Carbon::parse($month)->year)
            ->orderBy('dtr_timestamp')
            ->get();

        // Process the DTR records to apply the old DTR system logic
        $processedDtrRecords = $this->processDtrRecords($dtrRecords, $month);

        $data = [
            'employee' => $employee,
            'processedDtrRecords' => $processedDtrRecords,
            'month' => Carbon::parse($month)->format('F Y'),
        ];

        // Log the processed data for debugging
        Log::info('Processed DTR Records Data:', $processedDtrRecords);

        $pdf = Pdf::loadView('filament.resources.hr.dtr-resource.pages.dtr_pdf', $data);

        return $pdf->stream('DTR_' . $month . '_' . $employee->full_name . '.pdf');
    }

    protected function processDtrRecords($dtrRecords, $month)
    {
        $date = Carbon::parse($month);
        $number_of_days = $date->daysInMonth;

        // Initialize the data array with empty records for each day of the month
        $data = [];
        for ($i = 1; $i <= $number_of_days; ++$i) {
            $d = $date->copy()->day($i);
            $data[$i] = [
                'day' => $i,
                'dayname' => $d->format('D'),
                'am_in' => null,
                'am_out' => null,
                'pm_in' => null,
                'pm_out' => null,
                'hrs_rendered' => null,
                'remarks' => null,
            ];
        }

        // Add holiday remarks
        $holidays = Holiday::whereYear('holiday_date', $date->year)
            ->whereMonth('holiday_date', $date->month)
            ->orderBy('holiday_date')
            ->get();

        foreach ($holidays as $holiday) {
            $current_day = Carbon::parse($holiday->holiday_date)->day; // Parse the date and get the day of the month
            $data[$current_day]["remarks"] = $holiday->description;
        }

        // Ensure $employee is defined before the closure
        $employee = Auth::user()->employee;

        // Add travel order remarks
        $travelOrders = TravelOrder::where('status', 'completed')
            ->whereHas('details', function ($query) use ($employee) {
                $query->where('employee_id', $employee->id);
            })
            ->where(function ($query) use ($date) {
                $query->whereYear('inclusive_start_date', $date->year)
                    ->whereMonth('inclusive_start_date', $date->month)
                    ->orWhere(function ($query) use ($date) {
                        $query->whereYear('inclusive_end_date', $date->year)
                            ->whereMonth('inclusive_end_date', $date->month);
                    });
            })
            ->get();


        foreach ($travelOrders as $order) {
            $travel_start = Carbon::parse($order->inclusive_start_date);
            $travel_end = Carbon::parse($order->inclusive_end_date);
            for ($d = $travel_start->day; $d <= $travel_end->day; $d++) {
                if (isset($data[$d])) {
                    $data[$d]["remarks"] .= ($data[$d]["remarks"] ? " | " : "") . "Travel Order No: " . $order->id . " (Travel)";
                }
            }
        }
        // Add leave remarks
        $leaves = Leave::where('employee_id', $employee->id)
            ->where('leave_status', 'approved')
            ->whereHas('leaveDetails', function ($query) use ($date) {
                $query->whereYear('leave_date', $date->year)
                    ->whereMonth('leave_date', $date->month);
            })
            ->with('leaveDetails')
            ->get();

        foreach ($leaves as $leave) {
            foreach ($leave->leaveDetails as $leaveDetail) {
                $leave_day = Carbon::parse($leaveDetail->leave_date)->day;
                if (isset($data[$leave_day])) {
                    $data[$leave_day]["remarks"] .= ($data[$leave_day]["remarks"] ? " | " : "") . "Leave No: " . $leave->id;
                }
            }
        }

        // Process DTR records
        foreach ($dtrRecords as $dtr) {
            $current_day = Carbon::parse($dtr->dtr_timestamp)->day;

            if (!$data[$current_day]["am_in"] && $dtr->log_type == "IN") {
                $data[$current_day]["am_in"] = Carbon::parse($dtr->dtr_timestamp)->format('h:i a');
            } elseif (!$data[$current_day]["am_out"] && $dtr->log_type == "BREAK OUT") {
                $data[$current_day]["am_out"] = Carbon::parse($dtr->dtr_timestamp)->format('h:i a');
            } elseif (!$data[$current_day]["pm_in"] && $dtr->log_type == "BREAK IN") {
                $data[$current_day]["pm_in"] = Carbon::parse($dtr->dtr_timestamp)->format('h:i a');
            } elseif (!$data[$current_day]["pm_out"] && $dtr->log_type == "OUT") {
                $data[$current_day]["pm_out"] = Carbon::parse($dtr->dtr_timestamp)->format('h:i a');
            }
        }

        // Compute hours rendered for each day
        $am_start = strtotime("07:00");
        $pm_end = strtotime("18:00");




        foreach ($data as &$day) {
            if (empty($day['remarks']) || strpos($day['remarks'], "Holiday") === false) { // Only compute if it's not a holiday
                if (!empty($day['am_in']) && !empty($day['am_out']) && !empty($day['pm_in']) && !empty($day['pm_out'])) {
                    $s1 = strtotime($day['am_in']);
                    $e1 = strtotime($day['am_out']);
                    $s2 = strtotime($day['pm_in']);
                    $e2 = strtotime($day['pm_out']);

                    $s1 = max($s1, $am_start);
                    $e2 = min($e2, $pm_end);

                    $total_hours = $this->getDiffInMins($s1, $e1) + $this->getDiffInMins($s2, $e2);

                    // Adjust for lunch break if less than 60 mins
                    $lunch_break = $this->getDiffInMins($e1, $s2);
                    if ($lunch_break < 60) {
                        $total_hours -= (60 - $lunch_break);
                    }

                    $day['hrs_rendered'] = $total_hours / 60;
                } elseif (!empty($day['am_in']) && !empty($day['am_out']) && empty($day['pm_in']) && empty($day['pm_out'])) {
                    $s1 = strtotime($day['am_in']);
                    $e1 = strtotime($day['am_out']);
                    $s1 = max($s1, $am_start);

                    $day['hrs_rendered'] = $this->getDiffInMins($s1, $e1) / 60;
                } elseif (empty($day['am_in']) && empty($day['am_out']) && !empty($day['pm_in']) && !empty($day['pm_out'])) {
                    $s2 = strtotime($day['pm_in']);
                    $e2 = strtotime($day['pm_out']);
                    $e2 = min($e2, $pm_end);

                    $day['hrs_rendered'] = $this->getDiffInMins($s2, $e2) / 60;
                }
            }
        }






        return $data;
    }

    private function getDiffInMins($t1, $t2)
    {
        return round(abs(($t2 - $t1) / 60));
    }
}
