<?php

namespace App\Filament\Resources\RoutingSlip\DocumentResource\Pages;

use App\Filament\Resources\RoutingSlip\DocumentResource;
use App\Http\Controllers\RoutingSlipMailerController;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['status'] = 'created';
        
        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $record = parent::handleRecordCreation($data);

        // Handle file uploads for digital documents only
        if ($data['document_type'] === 'digital' && isset($data['files'])) {
            foreach ($data['files'] as $file) {
                $record->files()->create([
                    'file_path' => $file,
                    'file_name' => basename($file),
                    'mime_type' => Storage::disk('public')->mimeType($file),
                    'file_size' => Storage::disk('public')->size($file),
                ]);
            }
        }
        
        // Send emails to CC recipients if provided
        if (isset($data['cc_recipients']) && is_array($data['cc_recipients']) && !empty($data['cc_recipients'])) {
            // Store CC recipients in the database first
            foreach ($data['cc_recipients'] as $ccRecipient) {
                if (!empty($ccRecipient['email'])) {
                    $record->ccRecipients()->create([
                        'user_id' => $ccRecipient['user_id'] ?? null,
                        'name' => $ccRecipient['name'] ?? $ccRecipient['email'],
                        'position' => $ccRecipient['position'] ?? '',
                        'division' => $ccRecipient['division'] ?? '',
                        'email' => $ccRecipient['email'],
                        'remarks' => $data['remarks'] ?? null,
                    ]);
                }
            }
            
            // Now send emails to all CC recipients (will fetch from database)
            $mailerController = new RoutingSlipMailerController();
            $mailerController->sendCCEmails(
                $record,
                [], // No additional recipients needed as they're now in the database
                $data['remarks'] ?? null
            );
        }

        return $record;
    }
    
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('workflow', ['record' => $this->record]);
    }
}
