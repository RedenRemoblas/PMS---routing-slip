<?php

namespace App\Filament\Resources\RoutingSlip\DocumentResource\Pages;

use App\Filament\Resources\RoutingSlip\DocumentResource;
use App\Filament\Resources\RoutingSlip\ReviewDocumentResource;
use App\Models\Document\RoutingSlip;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;

class DocumentWorkflow extends Page
{
    protected static string $resource = DocumentResource::class;

    protected static string $view = 'filament.resources.routing-slip.document-resource.pages.document-workflow';

    public RoutingSlip $record;

    public function mount(RoutingSlip $record): void
    {
        // Check if the user is the creator of the document
        if ($record->created_by !== Auth::id()) {
            Notification::make()
                ->title('Unauthorized')
                ->body('You are not authorized to access this page.')
                ->danger()
                ->send();

            $this->redirect(DocumentResource::getUrl('index'));
        }

        // Check if the document is already locked or finalized
        if ($record->status !== 'created') {
            Notification::make()
                ->title('Document Already Processed')
                ->body('This document has already been processed.')
                ->warning()
                ->send();

            $this->redirect(DocumentResource::getUrl('view', ['record' => $record]));
        }

        $this->record = $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('lockDocument')
                ->label('Lock Document & Send for Review')
                ->icon('heroicon-o-lock-closed')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Lock Document')
                ->modalDescription('Are you sure you want to lock this document? This will finalize it and send it for review. You will not be able to edit it after locking. Note: The document will not be available for review until you lock it.')
                ->modalSubmitActionLabel('Yes, Lock Document')
                ->action(function () {
                    // Update document status to pending (ready for review)
                    $this->record->update(['status' => 'pending']);
                    
                    // Notify the first reviewer in the sequence
                    $firstSequence = $this->record->sequences()
                        ->orderBy('sequence_number')
                        ->first();
                        
                    if ($firstSequence) {
                        $firstSequence->user->notify(new \App\Notifications\RoutingSlipActionRequired($this->record));
                    }
                    
                    Notification::make()
                        ->title('Document Locked')
                        ->body('The document has been locked and sent for review. Approvers can now review the document.')
                        ->success()
                        ->send();
                        
                    $this->redirect(ReviewDocumentResource::getUrl('index'));
                }),
                
            Action::make('editDocument')
                ->label('Edit Document')
                ->icon('heroicon-o-pencil')
                ->color('warning')
                ->url(fn () => DocumentResource::getUrl('edit', ['record' => $this->record])),
                
            Action::make('cancelDocument')
                ->label('Cancel Document')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Cancel Document')
                ->modalDescription('Are you sure you want to cancel this document? This action cannot be undone.')
                ->modalSubmitActionLabel('Yes, Cancel Document')
                ->action(function () {
                    // Delete the document
                    $this->record->delete();
                    
                    Notification::make()
                        ->title('Document Cancelled')
                        ->body('The document has been cancelled and removed.')
                        ->success()
                        ->send();
                        
                    $this->redirect(DocumentResource::getUrl('index'));
                }),
        ];
    }
}