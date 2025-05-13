<?php

namespace App\Http\Controllers;

use App\Models\EmployeeCertificate;
use App\Models\Hr\OvertimeOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PDF;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\Tcpdf\Fpdi as TcpdfFpdi;

class OvertimePdfController extends Controller
{


    public function generatePdf(OvertimeOrder $overtimeOrder)
    {
        // Ensure relationships are loaded
        $overtimeOrder->load(['details.employee.position', 'details.employee.division', 'approvalStages.employee']);

        // Prepare the data for the view
        $data = [
            'overtimeOrder' => $overtimeOrder,
            'overtimeOrderDetails' => $overtimeOrder->details, // Pass raw details
            'approvalStages' => $overtimeOrder->approvalStages->map(function ($stage) {
                return [
                    'sequence' => $stage->sequence,
                    'approver_name' => $stage->employee->full_name,
                    'status' => $stage->status,
                    'date' => $stage->updated_at->format('F j, Y'),
                    'remarks' => $stage->remarks,
                ];
            }),
        ];

        Log::info('Overtime PDF Data:', $data);

        // Generate the PDF in memory
        $pdf = PDF::loadView('overtime_order', $data);

        // Output the PDF without signature
        $pdfContent = $pdf->output();

        // Return the PDF to the browser
        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="overtime_order_' . $overtimeOrder->id . '.pdf"',
        ]);
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
            'Reason' => 'Testing',
            'ContactInfo' => '',
        ]);

        // Add a signature appearance to the document
        $pdf->SetFont('helvetica', '', 8); // Set font size to 8
        $pdf->SetXY(160, 167);
        $pdf->Cell(0, 12, 'Digitally signed by ' . $employeeFullName, 0, 1, 'C');

        $pdf->Output($outputPdfPath, 'F');
    }
}

