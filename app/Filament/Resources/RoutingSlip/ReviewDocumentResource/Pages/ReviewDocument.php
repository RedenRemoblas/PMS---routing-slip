<?php

namespace App\Filament\Resources\RoutingSlip\ReviewDocumentResource\Pages;

use App\Filament\Resources\RoutingSlip\ReviewDocumentResource;
use App\Models\Document\RoutingSlip;
use App\Models\Document\RoutingSlipFile;
use App\Notifications\RoutingSlipActionRequired;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ReviewDocument extends Page
{
    use WithFileUploads;
    
    protected static string $resource = ReviewDocumentResource::class;

    protected static string $view = 'filament.resources.routing-slip.document-resource.pages.review-document';

    public RoutingSlip $record;

    public function mount(RoutingSlip $record): void
    {
        // Set a higher execution time limit for this page
        set_time_limit(600); // 10 minutes
        
        // Eager load relationships to improve performance
        $this->record = $record->load(['creator', 'sequences.user', 'files']);
        
        // Check if the current user is in the approval sequence but not the current approver
        $userSequence = $this->record->sequences()
            ->where('user_id', auth()->id())
            ->first();
            
        $currentSequence = $this->record->sequences()
            ->where('status', 'pending')
            ->orderBy('sequence_number')
            ->first();
            
        if ($userSequence && $currentSequence && $userSequence->id !== $currentSequence->id) {
            // User is in the sequence but not the current approver
            $currentApprover = $currentSequence->user->name;
            $sequenceNumber = $currentSequence->sequence_number;
            
            // Show notification that document is waiting for previous approver
            Notification::make()
                ->title('Waiting for Previous Approver')
                ->body("This document is currently waiting for approval from {$currentApprover} (Sequence #{$sequenceNumber}). You will be notified when it's your turn to review.")
                ->warning()
                ->persistent()
                ->send();
        }
    }
    
    /**
     * Update document information
     */
    public function updateDocument(Request $request, $id)
    {
        $routingSlip = RoutingSlip::findOrFail($id);
        
        // Check if document is already approved or rejected
        if ($routingSlip->status === 'approved' || $routingSlip->status === 'rejected') {
            Notification::make()
                ->title('Error')
                ->body('This document has been finalized and cannot be edited.')
                ->danger()
                ->send();
                
            return redirect()->back();
        }
        
        // Update document information
        $routingSlip->update([
            'title' => $request->title,
            'remarks' => $request->remarks,
        ]);
        
        Notification::make()
            ->title('Success')
            ->body('Document information has been updated.')
            ->success()
            ->send();
            
        return redirect()->back();
    }

    protected function getHeaderActions(): array
    {
        $currentSequence = $this->record->sequences()
            ->where('status', 'pending')
            ->orderBy('sequence_number')
            ->first();
            
        // Check if all sequences are completed (approved or rejected)
        $allSequencesCompleted = $this->record->sequences()->where('status', 'pending')->count() === 0;
        
        // If all sequences are completed, show the lock document button
        if ($allSequencesCompleted && $this->record->status === 'pending') {
            return [
                Action::make('lockDocument')
                    ->label('Lock Document')
                    ->icon('heroicon-o-lock-closed')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Lock Document')
                    ->modalDescription('Are you sure you want to lock this document? This action cannot be undone and will prevent any further edits.')
                    ->modalSubmitActionLabel('Yes, Lock Document')
                    ->action(function () {
                        try {
                            DB::beginTransaction();
                            
                            // Update document status to approved
                            $this->record->update(['status' => 'approved']);
                            
                            DB::commit();
                            
                            Notification::make()
                                ->title('Document Locked')
                                ->body('The document has been locked and can no longer be edited.')
                                ->success()
                                ->send();
                                
                            $this->redirect(ReviewDocumentResource::getUrl('index'));
                        } catch (\Exception $e) {
                            DB::rollBack();
                            
                            Notification::make()
                                ->title('Error')
                                ->body('An error occurred while locking the document: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
            ];
        }
        
        // Only show approval actions if the current user is the one who needs to approve
        if (!$currentSequence || $currentSequence->user_id !== auth()->id()) {
            return [];
        }

        return [
            Action::make('approve')
                ->label('Approve Document')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->disabled(fn () => $this->record->status === 'approved' || $this->record->status === 'rejected')
                ->form([
                    Textarea::make('remarks')
                        ->label('Remarks')
                        ->placeholder('Enter your approval remarks here...')
                        ->required(),
                    FileUpload::make('attachments')
                        ->label('Upload Signed Documents')
                        ->helperText('Upload any signed documents or supporting files (PDF, Word, Images)')
                        ->multiple()
                        ->directory('routing-slip-attachments')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                        ->maxSize(1024 * 1024 * 10) // 10MB limit for better performance
                ])
                ->action(function (array $data) use ($currentSequence) {
                    // Use database transaction to ensure data integrity and improve performance
                    DB::beginTransaction();
                    
                    try {
                        // Update current sequence
                        $currentSequence->update([
                            'status' => 'approved',
                            'remarks' => $data['remarks'],
                            'acted_at' => now(),
                        ]);
                        
                        // Save uploaded files if any - process in batch for better performance
                        if (isset($data['attachments']) && !empty($data['attachments'])) {
                            $fileRecords = [];
                            
                            foreach ($data['attachments'] as $file) {
                                // Store the file
                                $path = $file->store('routing-slip-attachments');
                                
                                // Prepare file record
                                $fileRecords[] = [
                                    'routing_slip_id' => $this->record->id,
                                    'file_path' => $path,
                                    'file_name' => $file->getClientOriginalName(),
                                    'file_size' => $file->getSize(),
                                    'mime_type' => $file->getMimeType(),
                                    'uploaded_by' => auth()->id(),
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ];
                            }
                            
                            // Insert all files at once
                            if (!empty($fileRecords)) {
                                RoutingSlipFile::insert($fileRecords);
                            }
                        }
                        
                        // Find next sequence
                        $nextSequence = $this->record->sequences()
                            ->where('sequence_number', '>', $currentSequence->sequence_number)
                            ->orderBy('sequence_number')
                            ->first();

                        if ($nextSequence) {
                            // Notify next user
                            $nextSequence->user->notify(new RoutingSlipActionRequired($this->record));
                            
                            Notification::make()
                                ->title('Document Approved')
                                ->body('The document has been approved and sent to the next approver.')
                                ->success()
                                ->send();
                        } else {
                            // If no next sequence, mark document as approved
                            $this->record->update(['status' => 'approved']);
                            
                            Notification::make()
                                ->title('Document Approved')
                                ->body('The document has been approved and finalized.')
                                ->success()
                                ->send();
                        }
                        
                        DB::commit();
                        $this->redirect(ReviewDocumentResource::getUrl('index'));
                        
                    } catch (\Exception $e) {
                        DB::rollBack();
                        Notification::make()
                            ->title('Error')
                            ->body('An error occurred while processing your request: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('reject')
                ->label('Reject Document')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->disabled(fn () => $this->record->status === 'approved' || $this->record->status === 'rejected')
                ->requiresConfirmation()
                ->modalHeading('Reject Document')
                ->modalDescription('Are you sure you want to reject this document? This action cannot be undone.')
                ->modalSubmitActionLabel('Yes, Reject Document')
                ->form([
                    Textarea::make('remarks')
                        ->label('Rejection Reason')
                        ->placeholder('Please provide a reason for rejecting this document...')
                        ->required(),
                    FileUpload::make('attachments')
                        ->label('Upload Supporting Documents')
                        ->helperText('Upload any supporting documents for the rejection (optional)')
                        ->multiple()
                        ->directory('routing-slip-attachments')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                        ->maxSize(1024 * 1024 * 10) // 10MB limit for better performance
                ])
                ->action(function (array $data) use ($currentSequence) {
                    // Use database transaction to ensure data integrity and improve performance
                    DB::beginTransaction();
                    
                    try {
                        // Update current sequence
                        $currentSequence->update([
                            'status' => 'rejected',
                            'remarks' => $data['remarks'],
                            'acted_at' => now(),
                        ]);

                        // Save uploaded files if any - process in batch for better performance
                        if (isset($data['attachments']) && !empty($data['attachments'])) {
                            $fileRecords = [];
                            
                            foreach ($data['attachments'] as $file) {
                                // Store the file
                                $path = $file->store('routing-slip-attachments');
                                
                                // Prepare file record
                                $fileRecords[] = [
                                    'routing_slip_id' => $this->record->id,
                                    'file_path' => $path,
                                    'file_name' => $file->getClientOriginalName(),
                                    'file_size' => $file->getSize(),
                                    'mime_type' => $file->getMimeType(),
                                    'uploaded_by' => auth()->id(),
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ];
                            }
                            
                            // Insert all files at once
                            if (!empty($fileRecords)) {
                                RoutingSlipFile::insert($fileRecords);
                            }
                        }

                        // Mark document as rejected
                        $this->record->update(['status' => 'rejected']);
                        
                        // Notify document creator
                        if ($this->record->creator) {
                            $this->record->creator->notify(new RoutingSlipActionRequired($this->record));
                        }
                        
                        DB::commit();
                        
                        Notification::make()
                            ->title('Document Rejected')
                            ->body('The document has been rejected.')
                            ->warning()
                            ->send();
                            
                        $this->redirect(ReviewDocumentResource::getUrl('index'));
                        
                    } catch (\Exception $e) {
                        DB::rollBack();
                        Notification::make()
                            ->title('Error')
                            ->body('An error occurred while processing your request: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}