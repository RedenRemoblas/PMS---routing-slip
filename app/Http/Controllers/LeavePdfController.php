<?php

namespace App\Http\Controllers;

use App\Models\EmployeeCertificate;
use App\Models\Hr\Leave;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PDF;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\Tcpdf\Fpdi as TcpdfFpdi;

class LeavePdfController extends Controller
{


    public function generatePdf(Leave $leave)
    {
        $employeeFullName = str_replace(' ', '_', $leave->employee->full_name);

        $data = [
            'leave' => $leave,
            'employee' => $leave->employee,
            'leaveType' => $leave->leaveType,
            'leaveDetails' => $leave->leaveDetails,
        ];

        Log::info('Leave PDF Data:', $data);

        $leave->load(['leaveType', 'employee', 'leaveDetails']);

        // Check if leave type is missing
        if (!$leave->leaveType) {
            abort(404, 'Leave type not found.');
        }

        // Generate the PDF in memory
        $pdf = PDF::loadView('leave_application', $data);

        // Fetch employee certificate and scanned signature for the current user
        $currentUserEmployeeId = Auth::user()->employee->id;
        $employeeCertificate = EmployeeCertificate::where('employee_id', $currentUserEmployeeId)->first();

        if ($employeeCertificate) {
            // Add the signature and digitally sign the PDF
            $pdfContent = $this->addSignatureAndSignPdf($pdf->output(), $employeeCertificate, $employeeFullName);
        } else {
            // Output the PDF without signature if no certificate is found
            $pdfContent = $pdf->output();
        }

        // Create a short timestamp
        $timestamp = date('Ymd_His'); // Example format: "20250206_083212"

        // Construct a unique filename using the timestamp and leave_id
        $filename = "leave_application_{$timestamp}_{$leave->id}.pdf";

        // Define the file path in storage
        $filePath = "leave-applications/{$filename}";

        // Save the PDF to storage
        Storage::put($filePath, $pdfContent);

        // ✅ Update `application_file_path` in the Leave model
        $leave->updateApplicationFilePath(Storage::url($filePath));

        Log::info("PDF saved to {$filePath} and updated application_file_path.");

        // Return the PDF to the browser
        return response($pdfContent, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
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
