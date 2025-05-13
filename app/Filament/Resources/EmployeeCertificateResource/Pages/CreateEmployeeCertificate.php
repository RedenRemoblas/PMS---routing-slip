<?php

namespace App\Filament\Resources\EmployeeCertificateResource\Pages;

use Exception;
use App\Models\EmployeeCertificate;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;
use App\Filament\Resources\EmployeeCertificateResource;

class CreateEmployeeCertificate extends CreateRecord
{
    protected static string $resource = EmployeeCertificateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        try {

            // Ensure the employee_id is set based on the authenticated user
            $data['employee_id'] = auth()->user()->employee->id;


       // Check if the employee already has a certificate
       $existingCertificate = EmployeeCertificate::where('employee_id', $data['employee_id'])->first();
       if ($existingCertificate) {

            Notification::make()
                ->title('Error')
                ->body('This employee already has a certificate.')
                ->danger()
                ->send();

            // Redirect after the notification
           // $this->redirectToIndex();
            return [];

       }


            // Handle the uploaded .p12 file and extract data
            $p12FilePath = Storage::path('public/' . $data['p12_file']);
            $p12Password = $data['p12_password'];

            // Extract values from the .p12 file
            $p12Content = file_get_contents($p12FilePath);
            $certs = [];
            if (!openssl_pkcs12_read($p12Content, $certs, $p12Password)) {
                throw new Exception('Unable to read the .p12 file. The password might be incorrect.');
            }

            $data['private_key'] = $certs['pkey'];
            $data['certificate'] = $certs['cert'];
            $data['intermediate_certificates'] = isset($certs['extracerts']) ? implode("\n", $certs['extracerts']) : null;

            // Store the signature image path
            $data['signature_image_path'] = $data['signature_image'];

            // Remove the fields that are not part of the database schema
            unset($data['p12_file'], $data['p12_password'], $data['signature_image']);
        } catch (Exception $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();

            // Stop the creation process by throwing a ValidationException
            throw ValidationException::withMessages([
                'p12_password' => $e->getMessage(),
            ]);
        }

        return $data;
    }
}
