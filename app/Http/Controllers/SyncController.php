<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SyncController extends Controller
{
    public function syncData()
    {
        // Get the date two months ago from today
        $twoMonthsAgo = Carbon::now()->subMonths(2);
    
        // Connect to mysql_backup and fetch data for the past 2 months
        $data = DB::connection('mysql_backup')
            ->table('dtrs')
            ->where('dtr_timestamp', '>=', $twoMonthsAgo)
            ->get();
    
        // Check if $data is empty
        if ($data->isEmpty()) {
            Log::warning('No recent data found in mysql_backup.');
            return response()->json(['message' => 'No recent data found to sync.']);
        }
    
        // Loop through the data and insert or update into MySQL localhost
        foreach ($data as $record) {
            DB::connection('mysql')->table('dtrs')->insertOrIgnore([
                'id' => $record->id,
                'dtr_timestamp' => $record->dtr_timestamp,
                'log_type' => $record->log_type,
                'employee_dtr_no' => $record->employee_dtr_no,
                'device_serial_no' => $record->device_serial_no,
                'verify_mode' => $record->verify_mode,
                'sequence_no' => $record->sequence_no,
                'remarks' => $record->remarks,
                'created_at' => $record->created_at,
                'updated_at' => Carbon::now(),
                'deleted_at' => $record->deleted_at
            ]);
        }
        
    
        return response()->json(['message' => 'Data sync completed successfully!']);
    }
    
}
