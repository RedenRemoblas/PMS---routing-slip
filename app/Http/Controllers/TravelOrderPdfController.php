<?php

namespace App\Http\Controllers;

use App\Models\EmployeeCertificate;
use App\Models\Travel\TravelOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PDF;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\Tcpdf\Fpdi as TcpdfFpdi;

class TravelOrderPdfController extends Controller
{
    public function generatePdf(TravelOrder $travelOrder)
    {

        $travelOrderId = $travelOrder->id;
        $filePath = storage_path("app/public/travel_orders/travel_order_{$travelOrderId}.pdf");

        // Check if the PDF file already exists
        if (file_exists($filePath)) {
            return response()->file($filePath);
        }
        $employeeFullName = str_replace(' ', '_', $travelOrder->employee->full_name);

        $data = [
            'travelOrder' => $travelOrder,
            'travelOrderDetails' => $travelOrder->details->map(function ($detail) {
                return [
                    'employee_name' => $detail->employee->full_name,
                    'position' => $detail->employee->position->name,
                    'division' => $detail->employee->division->name,
                ];
            })->toArray(),  // Convert the collection to an array
            'approvalStages' => $travelOrder->approvalStages->map(function ($stage) {
                return [
                    'sequence' => $stage->sequence,
                    'approver_name' => $stage->employee->full_name,
                    'status' => $stage->status,
                    'date' => $stage->updated_at->format('F j, Y'),
                    'remarks' => $stage->remarks,
                ];
            })->toArray(),  // Convert the collection to an array
        ];

        Log::info('Travel Order PDF Data:', $data);

        // Ensure the 'travel_orders' directory exists
        $travelOrdersDirectory = storage_path('app/public/travel_orders');
        if (! is_dir($travelOrdersDirectory)) {
            mkdir($travelOrdersDirectory, 0755, true);
        }

        // Generate a unique filename based on the travel order ID
        $uniqueFilename = 'travel_order_'.$travelOrder->id.'.pdf';
        $pdfPath = $travelOrdersDirectory.'/'.$uniqueFilename;

        // Generate PDF and overwrite if it already exists
        $pdf = PDF::loadView('travel_order', $data);
        $pdf->save($pdfPath);

        // Ensure the file is completely written
        clearstatcache();
        while (! file_exists($pdfPath) || filesize($pdfPath) == 0) {
            usleep(50000); // wait 50ms before retrying
            clearstatcache();
        }

        /*  // Fetch employee certificate and scanned signature for the current user
          $currentUserEmployeeId = Auth::user()->employee->id;
          $employeeCertificate = EmployeeCertificate::where('employee_id', $currentUserEmployeeId)->first();

          if ($employeeCertificate) {
              // Overlay scanned signature
              $this->overlaySignature($pdfPath, Storage::path('public/' . $employeeCertificate->signature_image_path));

              // Digitally sign the PDF
              $this->digitallySignPdf($pdfPath, $pdfPath, $employeeCertificate, $employeeFullName);
          }
*/
        return response()->file($pdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$uniqueFilename.'"',
        ]);
    }

    public function downloadPdf(TravelOrder $travelOrder, $hash)
    {
        // Verify the hash to ensure the download is authorized
        $expectedHash = hash_hmac('sha256', $travelOrder->id, config('app.key'));
        
        if (!hash_equals($expectedHash, $hash)) {
            abort(403, 'Unauthorized access');
        }
        
        $travelOrderId = $travelOrder->id;
        $filePath = storage_path("app/public/travel_orders/travel_order_{$travelOrderId}.pdf");
        
        // Check if the PDF file exists, if not generate it
        if (!file_exists($filePath)) {
            // Prepare data for PDF generation
            $data = [
                'travelOrder' => $travelOrder,
                'travelOrderDetails' => $travelOrder->details->map(function ($detail) {
                    return [
                        'employee_name' => $detail->employee->full_name,
                        'position' => $detail->employee->position->name,
                        'division' => $detail->employee->division->name,
                    ];
                })->toArray(),
                'approvalStages' => $travelOrder->approvalStages->map(function ($stage) {
                    return [
                        'sequence' => $stage->sequence,
                        'approver_name' => $stage->employee->full_name,
                        'status' => $stage->status,
                        'date' => $stage->updated_at->format('F j, Y'),
                        'remarks' => $stage->remarks,
                    ];
                })->toArray(),
            ];
            
            // Ensure the directory exists
            $travelOrdersDirectory = storage_path('app/public/travel_orders');
            if (!is_dir($travelOrdersDirectory)) {
                mkdir($travelOrdersDirectory, 0755, true);
            }
            
            // Generate PDF
            $pdf = PDF::loadView('travel_order', $data);
            $pdf->save($filePath);
            
            // Ensure the file is completely written
            clearstatcache();
            while (!file_exists($filePath) || filesize($filePath) == 0) {
                usleep(50000); // wait 50ms before retrying
                clearstatcache();
            }
        }
        
        // Return the file for download
        return response()->file($filePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="travel_order_'.$travelOrder->id.'.pdf"',
        ]);
    }

    private function getTravelOrderData(TravelOrder $travelOrder)
    {
        // Fetch travel order data from the model
        return [
            'id' => $travelOrder->id,
            'employee_name' => $travelOrder->employee->full_name,
            'destination' => $travelOrder->destination,
            'start_date' => $travelOrder->start_date,
            'end_date' => $travelOrder->end_date,
            'status' => $travelOrder->status,
        ];
    }

    protected function overlaySignature($pdfPath, $signatureImagePath)
    {
        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($pdfPath);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);

            if ($pageNo === $pageCount) {
                $x = 160; // X coordinate
                $y = 160; // Y coordinate
                $width = 30; // Width of the signature image
                $height = 10; // Height of the signature image

                $pdf->Image($signatureImagePath, $x, $y, $width, $height);
            }
        }

        $pdf->Output($pdfPath, 'F');
    }

    protected function digitallySignPdf($inputPdfPath, $outputPdfPath, $employeeCertificate, $employeeFullName)
    {
        $privateKey = $employeeCertificate->private_key;
        $certificate = $employeeCertificate->certificate;

        // Create a new TCPDF object
        $pdf = new TcpdfFpdi();
        $pageCount = $pdf->setSourceFile($inputPdfPath);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);
        }

        // Create a digital signature
        $pdf->setSignature($certificate, $privateKey, 'tcpdfdemo', '', 2, [
            'Name' => $employeeFullName,
            'Location' => 'Your Location',
            'Reason' => 'Travel Order',
            'ContactInfo' => '',
        ]);

        // Add a signature appearance to the document
        $pdf->SetFont('helvetica', '', 8); // Set font size to 8
        $pdf->SetXY(160, 167);
        $pdf->Cell(0, 12, 'Digitally signed by '.$employeeFullName, 0, 1, 'C');

        $pdf->Output($outputPdfPath, 'F');
    }
}

