<?php

namespace App\Http\Controllers;

use App\Models\Document\RoutingSlip;
use App\Models\Document\RoutingSlipFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PDF;

class RoutingSlipPdfController extends Controller
{
    public function generatePdf(RoutingSlip $routingSlip)
    {
        // Increase the maximum execution time for PDF generation
        set_time_limit(300); // Set to 5 minutes
        
        $routingSlipId = $routingSlip->id;
        $currentDate = date('Y-m-d');
        $filePath = storage_path("app/public/routing_slips/routing_slip_{$routingSlipId}_{$currentDate}.pdf");

        // Ensure the 'routing_slips' directory exists
        $routingSlipsDirectory = storage_path('app/public/routing_slips');
        if (! is_dir($routingSlipsDirectory)) {
            mkdir($routingSlipsDirectory, 0755, true);
        }

        // Force regeneration if requested or prepare data for the PDF view
        $data = $this->prepareRoutingSlipData($routingSlip);

        Log::info('Routing Slip PDF Data:', $data);

        // Generate a unique filename based on the routing slip ID and current date
        $currentDate = date('Y-m-d');
        $uniqueFilename = 'routing_slip_'.$routingSlip->id.'_'.$currentDate.'.pdf';
        $pdfPath = $routingSlipsDirectory.'/'.$uniqueFilename;

        // Generate PDF and overwrite if it already exists
        $pdf = PDF::loadView('routing_slip', $data);
        
        // Set paper size and orientation
        $pdf->setPaper('a4', 'portrait');
        
        // Save the PDF
        $pdf->save($pdfPath);

        // Ensure the file is completely written
        clearstatcache();
        while (! file_exists($pdfPath) || filesize($pdfPath) == 0) {
            usleep(50000); // wait 50ms before retrying
            clearstatcache();
        }

        return response()->file($pdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$uniqueFilename.'"',
        ]);
    }

    public function downloadPdf(RoutingSlip $routingSlip, $hash)
    {
        // Increase the maximum execution time for PDF generation
        set_time_limit(300); // Set to 5 minutes
        
        // Verify the hash to ensure the download is authorized
        $expectedHash = hash_hmac('sha256', $routingSlip->id, config('app.key'));
        
        if (!hash_equals($expectedHash, $hash)) {
            abort(403, 'Unauthorized access');
        }
        
        $routingSlipId = $routingSlip->id;
        $currentDate = date('Y-m-d');
        $filePath = storage_path("app/public/routing_slips/routing_slip_{$routingSlipId}_{$currentDate}.pdf");
        
        // Ensure the directory exists
        $routingSlipsDirectory = storage_path('app/public/routing_slips');
        if (!is_dir($routingSlipsDirectory)) {
            mkdir($routingSlipsDirectory, 0755, true);
        }
        
        // Prepare data for PDF generation
        $data = $this->prepareRoutingSlipData($routingSlip);
        
        // Generate PDF
        $pdf = PDF::loadView('routing_slip', $data);
        
        // Set paper size and orientation
        $pdf->setPaper('a4', 'portrait');
        
        // Save the PDF
        $pdf->save($filePath);
        
        // Ensure the file is completely written
        clearstatcache();
        while (!file_exists($filePath) || filesize($filePath) == 0) {
            usleep(50000); // wait 50ms before retrying
            clearstatcache();
        }
        
        // Return the file for download
        return response()->file($filePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="routing_slip_'.$routingSlip->id.'_'.$currentDate.'.pdf"',
        ]);
    }
    
    /**
     * Prepare data for the routing slip PDF
     *
     * @param RoutingSlip $routingSlip
     * @return array
     */
    private function prepareRoutingSlipData(RoutingSlip $routingSlip)
    {
        // Load relationships to ensure all data is available
        $routingSlip->load(['creator', 'files', 'sequences.approver', 'sequences.approver.employee', 'sequences.approver.employee.position', 'sequences.approver.employee.division']);
        
        // Format the data for the PDF view
        return [
            'routingSlip' => $routingSlip,
            'document_type' => $routingSlip->document_type,
            'files' => $routingSlip->files->map(function ($file) {
                return [
                    'file_name' => $file->file_name,
                    'uploaded_at' => $file->created_at,
                    'file_size' => $this->formatFileSize($file->file_size),
                    'mime_type' => $file->mime_type,
                ];
            })->toArray(),
            'sequences' => $routingSlip->sequences->map(function ($sequence) {
                // Ensure position and division are properly attached
                $position = $sequence->position ?? ($sequence->approver && $sequence->approver->employee && $sequence->approver->employee->position ? $sequence->approver->employee->position->name : 'N/A');
                $division = $sequence->division ?? ($sequence->approver && $sequence->approver->employee && $sequence->approver->employee->division ? $sequence->approver->employee->division->name : 'N/A');
                
                return [
                    'sequence_number' => $sequence->sequence_number,
                    'approver_name' => $sequence->approver ? $sequence->approver->name : 'N/A',
                    'status' => $sequence->status,
                    'updated_at' => $sequence->updated_at,
                    'remarks' => $sequence->remarks,
                    'division' => $division,
                    'position' => $position,
                    'acted_at' => $sequence->acted_at,
                ];
            })->toArray(),
            'base_url' => url('/'),
            'dict_logo' => public_path('images/dict-logo.png'),
            'bagong_pilipinas_logo' => public_path('images/Bagong_Pilipinas_logo.png'),
        ];
    }
    
    /**
     * Format file size for display
     *
     * @param int $bytes
     * @return string
     */
    private function formatFileSize($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            return $bytes . ' bytes';
        } elseif ($bytes == 1) {
            return $bytes . ' byte';
        } else {
            return '0 bytes';
        }
    }
}