<?php

namespace App\Filament\Resources\RoutingSlip\DocumentResource\Pages;

use App\Filament\Resources\RoutingSlip\DocumentResource;
use App\Filament\Resources\RoutingSlip\ReviewDocumentResource;
use App\Models\Document\RoutingSlipFile;
use App\Notifications\RoutingSlipActionRequired;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Split;
use Filament\Support\Colors\Color;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Card;
use Filament\Infolists\Components\ImageEntry;
use Filament\Support\Enums\IconPosition;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class ViewDocument extends ViewRecord
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => auth()->id() === $this->record->created_by),
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->id() === $this->record->created_by),
            Actions\Action::make('generatePdf')
                ->label('Generate PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->url(fn () => route('routing-slip.pdf', ['routingSlip' => $this->record]))
                ->visible(fn () => $this->record->files !== null || $this->record->document_type === 'physical')
                ->openUrlInNewTab(),
            Actions\Action::make('lockAndSendToReview')
                ->label('Lock & Send to Review')
                ->icon('heroicon-o-lock-closed')
                ->color('success')
                ->visible(fn () => $this->record->status === 'created' && auth()->id() === $this->record->created_by)
                ->requiresConfirmation()
                ->modalHeading('Lock and Send Document for Review')
                ->modalDescription('Once locked, you will not be able to edit this document until the review process is complete or rejected.')
                ->modalSubmitActionLabel('Confirm and Send')
                ->action(function () {
                    // Update document status to pending
                    $this->record->update(['status' => 'pending']);
                    
                    // Notify the first approver in the sequence
                    $firstSequence = $this->record->sequences()
                        ->orderBy('sequence_number')
                        ->first();
                        
                    if ($firstSequence) {
                        $firstSequence->user->notify(new RoutingSlipActionRequired($this->record));
                    }
                    
                    Notification::make()
                        ->title('Document Locked and Sent for Review')
                        ->success()
                        ->send();
                        
                    // Redirect to review document page
                    $this->redirect(ReviewDocumentResource::getUrl('index'));
                }),
        ];
    }
    
    protected function getActions(): array
    {
        $actions = [
            Actions\Action::make('generatePdf')
                ->label('Generate PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->url(fn () => route('routing-slip.pdf', ['routingSlip' => $this->record]))
                ->visible(fn () => $this->record->files !== null || $this->record->document_type === 'physical')
                ->openUrlInNewTab()
        ];
        
        // Add View Workflow button if document is in 'created' status
        if ($this->record->status === 'created') {
            $actions[] = Actions\Action::make('viewWorkflow')
                ->label('Finalize Document')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('success')
                ->url(fn () => DocumentResource::getUrl('workflow', ['record' => $this->record]))
                ->tooltip('Return to document workflow page to lock, edit, or cancel document');
        }
        
        // Only get the current sequence if the document is in pending status (locked by creator)
        $currentSequence = null;
        if ($this->record->status === 'pending') {
            $currentSequence = $this->record->sequences()
                ->where('status', 'pending')
                ->orderBy('sequence_number')
                ->first();
        }

        // Only show approve/reject actions if the current user is the one who needs to approve
        if ($currentSequence && $currentSequence->user_id === auth()->id()) {
            $actions[] = Actions\Action::make('approve')
                ->color('success')
                ->disabled(fn () => $this->record->status === 'finalized')
                ->form([
                    Forms\Components\Textarea::make('remarks')
                        ->label('Remarks')
                        ->required(),
                    Forms\Components\FileUpload::make('attachments')
                        ->label('Upload Supporting Documents')
                        ->multiple()
                        ->directory('routing-slip-attachments')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                        ->maxSize(1024 * 1024 * 1024) // 1GB (effectively no limit)
                ])
                ->action(function (array $data) use ($currentSequence) {
                    // Use database transaction to ensure data integrity and improve performance
                    \Illuminate\Support\Facades\DB::beginTransaction();
                    
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
                                \App\Models\Document\RoutingSlipFile::insert($fileRecords);
                            }
                        }
                        
                        \Illuminate\Support\Facades\DB::commit();
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\DB::rollBack();
                        \Filament\Notifications\Notification::make()
                            ->title('Error')
                            ->body('An error occurred while processing your request: ' . $e->getMessage())
                            ->danger()
                            ->send();
                            
                        return;
                    }

                    // Find next sequence
                    $nextSequence = $this->record->sequences()
                        ->where('sequence_number', '>', $currentSequence->sequence_number)
                        ->orderBy('sequence_number')
                        ->first();

                    if ($nextSequence) {
                        // Notify next user
                        $nextSequence->user->notify(new \App\Notifications\RoutingSlipActionRequired($this->record));
                    } else {
                        // If no next sequence, mark document as approved
                        $this->record->update(['status' => 'approved']);
                    }

                    $this->redirect(\App\Filament\Resources\RoutingSlip\ReviewDocumentResource::getUrl('index'));
                });

            $actions[] = Actions\Action::make('reject')
                ->color('danger')
                ->disabled(fn () => $this->record->status === 'finalized')
                ->form([
                    Forms\Components\Textarea::make('remarks')
                        ->label('Remarks')
                        ->required(),
                    Forms\Components\FileUpload::make('attachments')
                        ->label('Upload Supporting Documents')
                        ->multiple()
                        ->directory('routing-slip-attachments')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                        ->maxSize(1024 * 1024 * 1024) // 1GB (effectively no limit)
                ])
                ->action(function (array $data) use ($currentSequence) {
                    // Update current sequence
                    $currentSequence->update([
                        'status' => 'rejected',
                        'remarks' => $data['remarks'],
                        'acted_at' => now(),
                    ]);

                    // Save uploaded files if any
                    if (isset($data['attachments']) && !empty($data['attachments'])) {
                        foreach ($data['attachments'] as $file) {
                            // Store the file
                            $path = $file->store('routing-slip-attachments');
                            
                            // Create file record
                            \App\Models\Document\RoutingSlipFile::create([
                                'routing_slip_id' => $this->record->id,
                                'file_path' => $path,
                                'file_name' => $file->getClientOriginalName(),
                                'file_size' => $file->getSize(),
                                'mime_type' => $file->getMimeType(),
                                'uploaded_by' => auth()->id(),
                            ]);
                        }
                    }

                    // Mark document as rejected
                    $this->record->update(['status' => 'rejected']);

                    $this->redirect(\App\Filament\Resources\RoutingSlip\ReviewDocumentResource::getUrl('index'));
                });
        }
        
        return $actions;
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Grid::make(3)
                    ->schema([
                        Card::make()
                            ->columnSpan(2)
                            ->schema([
                                Section::make()
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextEntry::make('id')
                                                    ->label('Tracking No.')
                                                    ->weight('bold')
                                                    ->color('primary')
                                                    ->size('lg')
                                                    ->prefix('RS-'),
                                                TextEntry::make('status')
                                                    ->badge()
                                                    ->size('lg')
                                                    ->color(fn (string $state): string => match ($state) {
                                                        'pending' => 'warning',
                                                        'approved' => 'success',
                                                        'rejected' => 'danger',
                                                        default => 'gray',
                                                    }),
                                                TextEntry::make('document_type')
                                                    ->label('Document Type')
                                                    ->badge()
                                                    ->size('lg')
                                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                                        'digital' => 'Digital Copy',
                                                        'physical' => 'Physical Copy',
                                                        default => $state,
                                                    })
                                                    ->color(fn (string $state): string => match ($state) {
                                                        'digital' => 'success',
                                                        'physical' => 'info',
                                                        default => 'gray',
                                                    })
                                                    ->icon(fn (string $state): string => match ($state) {
                                                        'digital' => 'heroicon-o-document',
                                                        'physical' => 'heroicon-o-clipboard-document',
                                                        default => 'heroicon-o-document',
                                                    }),
                                            ]),
                                        TextEntry::make('title')
                                            ->size('lg')
                                            ->weight('bold')
                                            ->columnSpanFull(),
                                        TextEntry::make('remarks')
                                            ->markdown()
                                            ->prose()
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('Uploaded Files')
                                    ->heading('Uploaded Files')
                                    ->icon('heroicon-o-paper-clip')
                                    ->collapsible()
                                    ->visible(fn ($record) => $record->document_type === 'digital')
                                    ->extraAttributes(['class' => 'mb-4'])
                                    ->description(fn ($record) => $record->document_type === 'digital' ? 'Digital documents attached to this routing slip' : null)
                                    ->schema([
                                        RepeatableEntry::make('files')
                                            ->schema([
                                                Grid::make(3)
                                                    ->schema([
                                                        IconEntry::make('mime_type')
                                                            ->icon(fn ($state) => match (explode('/', $state)[1] ?? '') {
                                                                'pdf' => 'heroicon-o-document',
                                                                'msword', 'vnd.openxmlformats-officedocument.wordprocessingml.document' => 'heroicon-o-document-text',
                                                                'jpeg', 'png', 'gif' => 'heroicon-o-photo',
                                                                default => 'heroicon-o-paper-clip',
                                                            })
                                                            ->color('primary'),
                                                        TextEntry::make('file_name')
                                                            ->label('File')
                                                            ->weight('medium')
                                                            ->helperText(fn ($record) => 
                                                                number_format($record->file_size / 1024, 2) . ' KB'
                                                            ),
                                                        TextEntry::make('download_url')
                                                            ->label('')
                                                            ->url(fn ($state) => $state)
                                                            ->openUrlInNewTab()
                                                            ->color('primary')
                                                            ->formatStateUsing(fn () => 'Download')
                                                            ->icon('heroicon-m-arrow-down-tray')
                                                            ->alignEnd(),
                                                    ]),
                                            ])
                                            ->contained(false),
                                    ]),
                                    
                                Section::make('Physical Document Information')
                                    ->heading('Physical Document Information')
                                    ->icon('heroicon-o-clipboard-document')
                                    ->collapsible()
                                    ->visible(fn ($record) => $record->document_type === 'physical')
                                    ->extraAttributes(['class' => 'mb-4'])
                                    ->description('This is a physical document that requires manual handling')
                                    ->schema([
                                        IconEntry::make('document_type')
                                            ->label('Document Type')
                                            ->icon('heroicon-o-clipboard-document')
                                            ->color('info'),
                                        TextEntry::make('physical_document_note')
                                            ->label('Note')
                                            ->formatStateUsing(fn () => 'This document exists in physical form and must be manually routed to each reviewer.')
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('Routing History')
                                    ->heading('Routing History')
                                    ->description('Complete document routing sequence and approval history')
                                    ->icon('heroicon-o-user-group')
                                    ->collapsible()
                                    ->schema([
                                        RepeatableEntry::make('sequences')
                                            ->schema([
                                                Section::make()
                                                    ->extraAttributes(fn ($record) => [
                                                        'class' => match ($record->status) {
                                                            'approved' => 'border-l-4 border-success-500 bg-success-50',
                                                            'rejected' => 'border-l-4 border-danger-500 bg-danger-50',
                                                            'pending' => 'border-l-4 border-warning-500 bg-warning-50',
                                                            default => 'border-l-4 border-gray-500 bg-gray-50',
                                                        },
                                                    ])
                                                    ->schema([
                                                        Grid::make(2)
                                                            ->schema([
                                                                TextEntry::make('user.name')
                                                                    ->label('User')
                                                                    ->weight('medium')
                                                                    ->size('lg')
                                                                    ->icon(fn ($record) => match ($record->status) {
                                                                        'approved' => 'heroicon-m-check-circle',
                                                                        'rejected' => 'heroicon-m-x-circle',
                                                                        default => 'heroicon-m-clock',
                                                                    })
                                                                    ->iconColor(fn ($record) => match ($record->status) {
                                                                        'approved' => 'success',
                                                                        'rejected' => 'danger',
                                                                        default => 'warning',
                                                                    })
                                                                    ->helperText(fn ($record) => $record->user?->position),
                                                                TextEntry::make('status')
                                                                    ->badge()
                                                                    ->size('lg')
                                                                    ->color(fn (string $state): string => match ($state) {
                                                                        'pending' => 'warning',
                                                                        'approved' => 'success',
                                                                        'rejected' => 'danger',
                                                                        default => 'gray',
                                                                    })
                                                                    ->alignEnd(),
                                                            ]),
                                                        Grid::make(2)
                                                            ->schema([
                                                                TextEntry::make('sequence_number')
                                                                    ->label('Step')
                                                                    ->weight('medium')
                                                                    ->badge()
                                                                    ->color(fn ($record) => $record->status === 'pending' ? 'gray' : 'primary'),
                                                                TextEntry::make('updated_at')
                                                                    ->label('Action Date')
                                                                    ->dateTime()
                                                                    ->visible(fn ($record) => $record->status !== 'pending')
                                                                    ->icon('heroicon-m-calendar')
                                                                    ->color('gray')
                                                                    ->size('sm')
                                                                    ->alignEnd(),
                                                            ]),
                                                        TextEntry::make('remarks')
                                                            ->label('Remarks')
                                                            ->visible(fn ($state) => !empty($state))
                                                            ->markdown()
                                                            ->prose()
                                                            ->columnSpanFull(),
                                                    ]),
                                            ])
                                            ->contained(false),
                                    ]),
                                    
                                Section::make('Physical Document Information')
                                    ->heading('Physical Document Information')
                                    ->icon('heroicon-o-clipboard-document')
                                    ->collapsible()
                                    ->visible(fn ($record) => $record->document_type === 'physical')
                                    ->extraAttributes(['class' => 'mb-4'])
                                    ->description('This is a physical document that requires manual handling')
                                    ->schema([
                                        IconEntry::make('document_type')
                                            ->label('Document Type')
                                            ->icon('heroicon-o-clipboard-document')
                                            ->color('info'),
                                        TextEntry::make('physical_document_note')
                                            ->label('Note')
                                            ->formatStateUsing(fn () => 'This document exists in physical form and must be manually routed to each reviewer.')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Card::make()
                            ->columnSpan(1)
                            ->schema([
                                Section::make('Document Details')
                                    ->heading('Document Details')
                                    ->icon('heroicon-o-information-circle')
                                    ->schema([
                                        TextEntry::make('created_at')
                                            ->label('Created At')
                                            ->dateTime()
                                            ->icon('heroicon-m-calendar'),
                                        TextEntry::make('creator.name')
                                            ->label('Created By')
                                            ->icon('heroicon-m-user')
                                            ->helperText(fn ($record) => $record->creator?->position),
                                    ]),

                                Section::make('Next Approver')
                                    ->heading('Next Approver')
                                    ->icon('heroicon-o-user-circle')
                                    ->extraAttributes(['class' => 'border-l-4 border-warning-500 bg-warning-50'])
                                    ->hidden(fn ($record) => $record->status !== 'pending')
                                    ->schema([
                                        TextEntry::make('sequences')
                                            ->state(function ($record) {
                                                $nextSequence = $record->sequences()
                                                    ->where('status', 'pending')
                                                    ->orderBy('sequence_number')
                                                    ->first();
                                                return $nextSequence?->user?->name ?? 'No pending approvers';
                                            })
                                            ->label('Name')
                                            ->icon('heroicon-m-user')
                                            ->weight('bold')
                                            ->size('lg')
                                            ->color('warning'),
                                        TextEntry::make('sequences')
                                            ->state(function ($record) {
                                                $nextSequence = $record->sequences()
                                                    ->where('status', 'pending')
                                                    ->orderBy('sequence_number')
                                                    ->first();
                                                return $nextSequence?->user?->position ?? '';
                                            })
                                            ->label('Position')
                                            ->visible(fn ($state) => !empty($state))
                                            ->icon('heroicon-m-briefcase'),
                                        TextEntry::make('sequences')
                                            ->state(function ($record) {
                                                $nextSequence = $record->sequences()
                                                    ->where('status', 'pending')
                                                    ->orderBy('sequence_number')
                                                    ->first();
                                                return "Step " . ($nextSequence?->sequence_number ?? '');
                                            })
                                            ->label('Current Step')
                                            ->visible(fn ($state) => !empty($state))
                                            ->badge()
                                            ->color('warning')
                                            ->icon('heroicon-m-arrow-right'),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
