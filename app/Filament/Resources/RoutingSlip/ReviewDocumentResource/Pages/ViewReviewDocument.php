<?php

namespace App\Filament\Resources\RoutingSlip\ReviewDocumentResource\Pages;

use App\Filament\Resources\RoutingSlip\ReviewDocumentResource;
use App\Models\Document\RoutingSlip;
use App\Models\Document\RoutingSlipFile;
use App\Notifications\RoutingSlipActionRequired;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Card;
use Filament\Infolists\Components\IconEntry;
use Illuminate\Support\Facades\Storage;
use Filament\Support\Colors\Color;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section as FormSection;
use Livewire\WithFileUploads;
use App\Models\Document\RoutingSlipCC;

class ViewReviewDocument extends ViewRecord
{
    use WithFileUploads;
    protected static string $resource = ReviewDocumentResource::class;
    
    // Property for routing slip ID
    public $routingSlipId;
    
    protected function getHeaderActions(): array
    {
        $currentSequence = $this->record->sequences()
            ->where('status', 'pending')
            ->orderBy('sequence_number')
            ->first();
            
        $actions = [
            Action::make('finalize')
                ->label('Lock Document')
                ->icon('heroicon-o-lock-closed')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Lock Document')
                ->modalDescription('Are you sure you want to lock this document? This action cannot be undone and will prevent any further edits.')
                ->modalSubmitActionLabel('Yes, Lock Document')
                ->action(function () {
                    $this->record->update(['status' => 'locked']);
                    
                    Notification::make()
                        ->title('Document Locked')
                        ->body('The document has been locked and can no longer be edited.')
                        ->success()
                        ->send();
                        
                    $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                })
                ->visible(fn (RoutingSlip $record): bool => 
                    // Only show manual lock button for rejected documents or if user is admin
                    // Approved documents are automatically locked
                    ($record->status === 'rejected') && 
                    (Auth::user()->hasRole('admin') || $record->created_by === Auth::id())
                ),
            
            Action::make('generatePdf')
                ->label('Generate PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->url(fn () => route('routing-slip.pdf', ['routingSlip' => $this->record]))
                ->openUrlInNewTab(),
        ];
        
        // Only show approve/reject actions if the current user is the one who needs to approve
        if ($currentSequence && $currentSequence->user_id === Auth::id() && $this->record->status !== 'locked') {
            $actions[] = Action::make('approve')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->form([
                    Textarea::make('remarks')
                        ->label('Remarks')
                        ->required()
                ])
                ->action(function (array $data) use ($currentSequence) {
                    // Use database transaction to ensure data integrity
                    DB::beginTransaction();
                    
                    try {
                        // Update current sequence
                        $currentSequence->update([
                            'status' => 'approved',
                            'remarks' => $data['remarks'],
                            'acted_at' => now(),
                        ]);
                        
                        DB::commit();
                    } catch (\Exception $e) {
                        DB::rollBack();
                        Notification::make()
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
                        $nextSequence->user->notify(new RoutingSlipActionRequired($this->record));
                    } else {
                        // If no next sequence, mark document as approved and automatically lock it
                        $this->record->update(['status' => 'approved']);
                        
                        Notification::make()
                            ->title('Document Approved')
                            ->body('All approvers have approved this document. The document is now approved.')
                            ->success()
                            ->send();
                    }

                    Notification::make()
                        ->title('Document Approved')
                        ->body('You have successfully approved this document.')
                        ->success()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                });

            $actions[] = Action::make('reject')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->form([
                    Textarea::make('remarks')
                        ->label('Remarks')
                        ->required()
                ])
                ->action(function (array $data) use ($currentSequence) {
                    // Use database transaction to ensure data integrity
                    DB::beginTransaction();
                    
                    try {
                        // Update current sequence
                        $currentSequence->update([
                            'status' => 'rejected',
                            'remarks' => $data['remarks'],
                            'acted_at' => now(),
                        ]);

                        // Mark document as rejected
                        $this->record->update(['status' => 'rejected']);
                        
                        DB::commit();
                    } catch (\Exception $e) {
                        DB::rollBack();
                        Notification::make()
                            ->title('Error')
                            ->body('An error occurred while processing your request: ' . $e->getMessage())
                            ->danger()
                            ->send();
                            
                        return;
                    }

                    Notification::make()
                        ->title('Document Rejected')
                        ->body('You have rejected this document.')
                        ->success()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                });
                
            $actions[] = Action::make('uploadDocuments')
                ->label('Upload Documents')
                ->icon('heroicon-o-paper-clip')
                ->color('success')
                ->form([
                    FileUpload::make('documents')
                        ->label('Documents')
                        ->multiple()
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/plain', 'text/csv', 'application/zip'])
                        ->directory('routing-slips/' . $this->record->id . '/documents')
                        ->maxSize(1024 * 1024 * 1024) // 1GB (effectively no limit)
                        ->helperText('Upload any documents related to this routing slip (PDF, Word, Excel, Images, Text, CSV, ZIP)')
                        ->required(),
                    Textarea::make('remarks')
                        ->label('Remarks')
                        ->placeholder('Add any notes about these documents'),
                    
                    // CC Recipients Section
                    FormSection::make('Carbon Copy (CC) Recipients')
                        ->description('Add people who should receive a copy of this document')
                        ->icon('heroicon-o-user-group')
                        ->collapsible()
                        ->schema([
                            Repeater::make('cc_recipients')
                                ->label('CC Recipients')
                                ->schema([
                                    TextInput::make('user_id')
                                        ->label('User ID')
                                        ->numeric()
                                        ->required(),
                                    TextInput::make('name')
                                        ->label('Name')
                                        ->required(false),
                                    TextInput::make('position')
                                        ->label('Position')
                                        ->required(),
                                    TextInput::make('division')
                                        ->label('Division')
                                        ->required(),
                                    TextInput::make('email')
                                        ->label('Email')
                                        ->email()
                                        ->required(),
                                ])
                                ->columns(2)
                                ->itemLabel(fn (array $state): ?string => $state['name'] ?? $state['email'] ?? null)
                                ->addActionLabel('Add CC Recipient')
                                ->reorderableWithButtons()
                                ->collapsible()
                        ])
                ])
                ->action(function (array $data) {
                    // Use database transaction to ensure data integrity
                    DB::beginTransaction();
                    
                    try {
                        // Process each uploaded file
                        foreach ($data['documents'] as $file) {
                            // Check if $file is a string (already stored path) or a file object
                            if (is_string($file)) {
                                // If it's a string, we already have the path
                                $path = $file;
                                $fileName = basename($path);
                                $fileSize = Storage::exists($path) ? Storage::size($path) : 0;
                                $mimeType = Storage::exists($path) ? Storage::mimeType($path) : 'application/octet-stream';
                            } else {
                                // If it's a file object, get the details and store it
                                $path = $file->store('routing-slips/' . $this->record->id . '/documents');
                                $fileName = $file->getClientOriginalName();
                                $fileSize = $file->getSize();
                                $mimeType = $file->getMimeType();
                            }
                            
                            // Create a new RoutingSlipFile record
                            RoutingSlipFile::create([
                                'routing_slip_id' => $this->record->id,
                                'file_path' => $path,
                                'file_name' => $fileName,
                                'file_size' => $fileSize,
                                'mime_type' => $mimeType,
                                'uploaded_by' => Auth::id(),
                                'file_type' => 'document', // Mark as a general document
                                'remarks' => $data['remarks'] ?? null,
                            ]);
                        }
                        
                        // Process CC recipients if provided
                        if (isset($data['cc_recipients']) && is_array($data['cc_recipients'])) {
                            // Store all CC recipients in the database
                            $storedRecipients = false;
                            
                            foreach ($data['cc_recipients'] as $ccRecipient) {
                                if (!empty($ccRecipient['email']) && filter_var($ccRecipient['email'], FILTER_VALIDATE_EMAIL)) {
                                    // Create a new RoutingSlipCC record for each recipient
                                    $this->record->ccRecipients()->create([
                                        'user_id' => $ccRecipient['user_id'] ?? null,
                                        'name' => $ccRecipient['name'] ?? $ccRecipient['email'], // Ensure name is populated, fallback to email if not provided
                                        'position' => $ccRecipient['position'] ?? '',
                                        'division' => $ccRecipient['division'] ?? '',
                                        'email' => $ccRecipient['email'],
                                        'remarks' => $data['remarks'] ?? null,
                                    ]);
                                    $storedRecipients = true;
                                }
                            }
                            
                            // Send emails to all CC recipients if any were stored
                            if ($storedRecipients) {
                                try {
                                    // Use the RoutingSlipMailerController to send emails to all CC recipients
                                    // The controller will fetch recipients from the database
                                    $mailerController = new \App\Http\Controllers\RoutingSlipMailerController();
                                    $mailerController->sendCCEmails(
                                        $this->record,
                                        [], // No additional recipients needed as they're now in the database
                                        $data['remarks'] ?? null
                                    );
                                } catch (\Exception $e) {
                                    // Log the error but continue processing
                                    \Illuminate\Support\Facades\Log::error("Failed to send CC emails: {$e->getMessage()}");
                                }
                            }
                        }
                    
                        DB::commit();
                        
                        Notification::make()
                            ->title('Documents Uploaded')
                            ->body('Documents have been successfully uploaded with CC recipients.')
                            ->success()
                            ->send();
                            
                    } catch (\Exception $e) {
                        DB::rollBack();
                        Notification::make()
                            ->title('Error')
                            ->body('An error occurred while uploading documents: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                    
                    $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                });
        }
        
        return $actions;
    }
    
    public function mount($record): void
    {
        parent::mount($record);
        $this->routingSlipId = $record;
    }
    
    /**
     * Safely get the MIME type of a file with proper error handling
     *
     * @param string $filePath The file path relative to storage
     * @return string The MIME type or a default value if detection fails
     */
    /**
     * Safely get the MIME type of a file with proper error handling
     *
     * @param string $filePath The file path relative to storage
     * @return string The MIME type or a default value if detection fails
     */
    protected function getSafeMimeType(string $filePath): string
    {
        try {
            // Get the full path to the file
            $fullPath = storage_path('app/' . $filePath);
            
            // First, try to determine MIME type from extension
            $extension = pathinfo($filePath, PATHINFO_EXTENSION);
            $mimeTypes = [
                'pdf' => 'application/pdf',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
            ];
            
            // Default MIME type based on extension
            $defaultMimeType = $mimeTypes[strtolower($extension)] ?? 'application/octet-stream';
            
            // Only attempt to use mime_content_type if the file actually exists
            if (file_exists($fullPath)) {
                return mime_content_type($fullPath);
            }
            
            // If file doesn't exist, return the default MIME type based on extension
            return $defaultMimeType;
        } catch (\Exception $e) {
            // Log the error but don't crash the application
            \Illuminate\Support\Facades\Log::error("MIME type detection failed: {$e->getMessage()}");
            return 'application/octet-stream'; // Default fallback MIME type
        }
    }
    
    // We've moved the file upload functionality to the ActionGroup in the infolist
    // This method is no longer needed as we're using Filament's built-in form handling
    
    // We've removed the removeUpload method as it's no longer needed
    // with the new ActionGroup implementation for file uploads
    
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // CC Recipients Section - Placed at the top for visibility
                Section::make('Carbon Copy (CC) Recipients')
                    ->visible(fn ($record) => $record->ccRecipients()->count() > 0)
                    ->icon('heroicon-o-user-group')
                    ->collapsible()
                    ->schema([
                        Group::make()
                            ->relationship('ccRecipients')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('name')
                                            ->label('Name')
                                            ->weight('bold'),
                                        TextEntry::make('position')
                                            ->label('Position'),
                                        TextEntry::make('division')
                                            ->label('Division'),
                                    ])
                            ])
                    ])
                    ->extraAttributes(['class' => 'mb-6 bg-blue-50 p-4 rounded-lg border border-blue-200']),
                    
                Grid::make(3)
                    ->extraAttributes(['class' => 'gap-y-8'])
                    ->schema([
                        Card::make()
                            ->columnSpan(2)
                            ->extraAttributes(['class' => 'border-t-4 border-primary-500 shadow-xl rounded-xl overflow-hidden transition-all duration-300'])
                            ->schema([
                                Section::make()
                                    ->extraAttributes(['class' => 'bg-white p-6 space-y-6'])
                                    ->schema([
                                        Grid::make(2)
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
                                                        'locked' => 'info',
                                                        default => 'gray',
                                                    }),
                                            ]),
                                            
                                        // Enhanced workflow progress indicator at the top
                                        TextEntry::make('workflow_progress')
                                            ->state(function ($record) {
                                                $totalSteps = $record->sequences()->count();
                                                $completedSteps = $record->sequences()->whereIn('status', ['approved', 'rejected'])->count();
                                                $progress = $totalSteps > 0 ? round(($completedSteps / $totalSteps) * 100) : 0;
                                                
                                                $progressBar = '';
                                                $progressBar .= '<div class="flex items-center mb-3">';
                                                $progressBar .= '<span class="text-xl font-bold mr-3 text-primary-700">' . $progress . '%</span>';
                                                $progressBar .= '<div class="w-full bg-gray-200 rounded-full h-4 dark:bg-gray-700">';
                                                $progressBar .= '<div class="bg-primary-600 h-4 rounded-full transition-all duration-500 ease-in-out" style="width: ' . $progress . '%"></div>';
                                                $progressBar .= '</div>';
                                                $progressBar .= '</div>';
                                                $progressBar .= '<div class="flex justify-between text-sm font-medium">';
                                                $progressBar .= '<span class="text-primary-700">' . $completedSteps . ' of ' . $totalSteps . ' steps completed</span>';
                                                if ($progress < 100) {
                                                    $progressBar .= '<span class="text-gray-600">' . ($totalSteps - $completedSteps) . ' steps remaining</span>';
                                                } else {
                                                    $progressBar .= '<span class="text-success-600 font-semibold">Completed</span>';
                                                }
                                                $progressBar .= '</div>';
                                                
                                                return $progressBar;
                                            })
                                            ->label('Approval Progress')
                                            ->icon('heroicon-o-chart-bar')
                                            ->html()
                                            ->extraAttributes(['class' => 'p-5 bg-gray-50 rounded-lg shadow-md mb-6 border border-gray-100 hover:shadow-lg transition-all duration-300'])
                                            ->columnSpanFull(),
                                            
                                        TextEntry::make('title')
                                            ->size('2xl')
                                            ->weight('bold')
                                            ->color('primary')
                                            ->icon('heroicon-o-document-text')
                                            ->extraAttributes(['class' => 'py-3 mb-2 border-b border-gray-100 pb-4'])
                                            ->columnSpanFull(),
                                        TextEntry::make('remarks')
                                            ->label('Document Remarks')
                                            ->icon('heroicon-o-chat-bubble-left-right')
                                            ->markdown()
                                            ->prose()
                                            ->extraAttributes(['class' => 'bg-gray-50 p-5 rounded-lg border border-gray-200 mt-3 shadow-inner hover:bg-gray-100 transition-colors duration-300'])
                                            ->size('lg')
                                            ->weight('medium')
                                            ->color('gray')
                                            ->columnSpanFull(),
                                    ]),


                                

                                
                                Section::make('Original Documents')
                                    ->heading('Original Documents')
                                    ->description('Documents originally attached to this routing slip')
                                    ->icon('heroicon-o-document')
                                    ->collapsible()
                                    ->extraAttributes(['class' => 'mb-8 bg-white shadow-md rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-300'])
                                    ->schema([
                                        Group::make()
                                            ->relationship('files', function ($query) {
                                                return $query->where('file_type', 'original');
                                            })
                                            ->schema([
                                                Grid::make(1)
                                                    ->extraAttributes(['class' => 'items-center p-4 hover:bg-gray-50 rounded-lg transition-colors duration-200 gap-4 border-b border-gray-100 last:border-b-0 hover:shadow-sm'])
                                                    ->schema([
                                                        Grid::make(5)
                                                            ->extraAttributes(['class' => 'items-center gap-4'])
                                                            ->schema([
                                                                IconEntry::make('mime_type')
                                                                    ->icon(fn ($state) => match (explode('/', $state)[1] ?? '') {
                                                                        'pdf' => 'heroicon-o-document',
                                                                        'msword', 'vnd.openxmlformats-officedocument.wordprocessingml.document' => 'heroicon-o-document-text',
                                                                        'jpeg', 'png', 'gif' => 'heroicon-o-photo',
                                                                        default => 'heroicon-o-paper-clip',
                                                                    })
                                                                    ->color('primary')
                                                                    ->size('lg')
                                                                    ->columnSpan(1),
                                                                
                                                                TextEntry::make('file_name')
                                                                    ->label('File')
                                                                    ->weight('medium')
                                                                    ->formatStateUsing(fn ($state, $record) => $state ?: basename($record->file_path))
                                                                    ->helperText(fn ($record) => 
                                                                        $record->file_size > 0 
                                                                            ? number_format($record->file_size / 1024, 2) . ' KB'
                                                                            : 'Unknown size'
                                                                    )
                                                                    ->columnSpan(1),
                                                                
                                                                TextEntry::make('uploader.name')
                                                                    ->label('Uploaded By')
                                                                    ->icon('heroicon-m-user')
                                                                    ->color('gray')
                                                                    ->weight('medium')
                                                                    ->columnSpan(1),
                                                                
                                                                TextEntry::make('remarks')
                                                                    ->label('Remarks')
                                                                    ->icon('heroicon-m-chat-bubble-left-ellipsis')
                                                                    ->color('gray')
                                                                    ->weight('medium')
                                                                    ->limit(50)
                                                                    ->tooltip(function ($state) {
                                                                        return $state ? $state : 'No remarks provided';
                                                                    })
                                                                    ->columnSpan(1),
                                                                
                                                                TextEntry::make('download_url')
                                                                    ->label('')
                                                                    ->url(fn ($state) => $state)
                                                                    ->openUrlInNewTab()
                                                                    ->color('primary')
                                                                    ->formatStateUsing(fn () => 'Download')
                                                                    ->icon('heroicon-m-arrow-down-tray')
                                                                    ->alignEnd()
                                                                    ->columnSpan(1),
                                                            ]),
                                                            
                                                        TextEntry::make('created_at')
                                                            ->label('Date Uploaded')
                                                            ->date('M j, Y - g:i A')
                                                            ->icon('heroicon-m-calendar')
                                                            ->color('gray')
                                                            ->size('sm')
                                                            ->extraAttributes(['class' => 'mt-1']),
                                                    ]),
                                            ])
                                            ->schema([]),
                                    ]),
                                    
                                Section::make('Signed Documents')
                                    ->heading('Signed Uploaded Documents')
                                    ->description('All documents uploaded during the review process')
                                    ->icon('heroicon-o-paper-clip')
                                    ->collapsible()
                                    ->extraAttributes(['class' => 'mb-8 bg-white shadow-md rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-300'])
                                    ->schema([
                                        Group::make()
                                            ->relationship('files', function ($query) {
                                                return $query->where('file_type', 'document');
                                            })
                                            ->schema([
                                                Grid::make(1)
                                                    ->extraAttributes(['class' => 'items-center p-4 hover:bg-gray-50 rounded-lg transition-colors duration-200 gap-4 border-b border-gray-100 last:border-b-0 hover:shadow-sm'])
                                                    ->schema([
                                                        Grid::make(5)
                                                            ->extraAttributes(['class' => 'items-center gap-4'])
                                                            ->schema([
                                                                IconEntry::make('mime_type')
                                                                    ->icon(fn ($state) => match (explode('/', $state)[1] ?? '') {
                                                                        'pdf' => 'heroicon-o-document',
                                                                        'msword', 'vnd.openxmlformats-officedocument.wordprocessingml.document' => 'heroicon-o-document-text',
                                                                        'jpeg', 'png', 'gif' => 'heroicon-o-photo',
                                                                        default => 'heroicon-o-paper-clip',
                                                                    })
                                                                    ->color('primary')
                                                                    ->size('lg')
                                                                    ->columnSpan(1),
                                                                
                                                                TextEntry::make('file_name')
                                                                    ->label('File')
                                                                    ->weight('medium')
                                                                    ->formatStateUsing(fn ($state, $record) => $state ?: basename($record->file_path))
                                                                    ->helperText(fn ($record) => 
                                                                        $record->file_size > 0 
                                                                            ? number_format($record->file_size / 1024, 2) . ' KB'
                                                                            : 'Unknown size'
                                                                    )
                                                                    ->columnSpan(1),
                                                                
                                                                TextEntry::make('uploader.name')
                                                                    ->label('Uploaded By')
                                                                    ->icon('heroicon-m-user')
                                                                    ->color('gray')
                                                                    ->weight('medium')
                                                                    ->columnSpan(1),
                                                                
                                                                TextEntry::make('remarks')
                                                                    ->label('Remarks')
                                                                    ->icon('heroicon-m-chat-bubble-left-ellipsis')
                                                                    ->color('gray')
                                                                    ->weight('medium')
                                                                    ->limit(50)
                                                                    ->tooltip(function ($state) {
                                                                        return $state ? $state : 'No remarks provided';
                                                                    })
                                                                    ->columnSpan(1),
                                                                
                                                                TextEntry::make('download_url')
                                                                    ->label('')
                                                                    ->url(fn ($state) => $state)
                                                                    ->openUrlInNewTab()
                                                                    ->color('primary')
                                                                    ->formatStateUsing(fn () => 'Download')
                                                                    ->icon('heroicon-m-arrow-down-tray')
                                                                    ->alignEnd()
                                                                    ->columnSpan(1),
                                                            ]),
                                                            
                                                        TextEntry::make('created_at')
                                                            ->label('Date Uploaded')
                                                            ->date('M j, Y - g:i A')
                                                            ->icon('heroicon-m-calendar')
                                                            ->color('gray')
                                                            ->size('sm')
                                                            ->extraAttributes(['class' => 'mt-1']),
                                                    ]),
                                            ])
                                            ->schema([]),
                                    ]),

                                Section::make('Routing History')
                                    ->heading('Routing History')
                                    ->description('Complete document routing sequence and approval history')
                                    ->icon('heroicon-o-user-group')
                                    ->collapsible()
                                    ->extraAttributes(['class' => 'bg-white shadow-md rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-300'])
                                    ->schema([
                                        Group::make()
                                            ->relationship('sequences')
                                            ->schema([
                                                Section::make()
                                                    ->extraAttributes(fn ($record) => [
                                                        'class' => match ($record->status) {
                                                            'approved' => 'border-l-4 border-success-500 bg-success-50 mb-4 rounded-r-lg shadow-md hover:shadow-lg transition-all duration-300',
                                                            'rejected' => 'border-l-4 border-danger-500 bg-danger-50 mb-4 rounded-r-lg shadow-md hover:shadow-lg transition-all duration-300',
                                                            'pending' => 'border-l-4 border-warning-500 bg-warning-50 mb-4 rounded-r-lg shadow-md hover:shadow-lg transition-all duration-300',
                                                            default => 'border-l-4 border-gray-500 bg-gray-50 mb-4 rounded-r-lg shadow-md hover:shadow-lg transition-all duration-300',
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
                                            ->schema([]),
                                    ]),
                            ]),

                        Card::make()
                            ->columnSpan(1)
                            ->extraAttributes(['class' => 'border-t-4 border-gray-500 shadow-xl rounded-xl h-fit sticky top-0 transition-all duration-300'])
                            ->schema([
                                Section::make('Document Details')
                                    ->heading('Document Details')
                                    ->icon('heroicon-o-information-circle')
                                    ->extraAttributes(['class' => 'bg-gray-50 rounded-lg mb-8 p-4 shadow-inner border border-gray-100'])
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

                                // Enhanced Next Approver section with better visual design
                                Section::make('Next Approver')
                                    ->heading('Next Approver')
                                    ->description('Current pending approval')
                                    ->icon('heroicon-o-user-circle')
                                    ->extraAttributes(['class' => 'border-l-4 border-warning-500 bg-warning-50 p-4 rounded-lg shadow-md mb-6 hover:shadow-lg transition-all duration-300'])
                                    ->hidden(fn ($record) => $record->status !== 'pending')
                                    ->schema([
                                        // Action required indicator
                                        IconEntry::make('action_required')
                                            ->label('Action Required')
                                            ->state(function ($record) {
                                                $nextSequence = $record->sequences()
                                                    ->where('status', 'pending')
                                                    ->orderBy('sequence_number')
                                                    ->first();
                                                    
                                                return $nextSequence && $nextSequence->user_id === auth()->id() ? 'yes' : 'no';
                                            })
                                            ->icon(fn ($state) => $state === 'yes' ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-clock')
                                            ->color(fn ($state) => $state === 'yes' ? 'danger' : 'gray')
                                            ->size('xl')
                                            ->helperText(fn ($state) => $state === 'yes' ? 'Your approval is required' : 'Waiting for approver action')
                                            ->columnSpanFull(),
                                            
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
                                            ->size('xl')
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
                                            ->icon('heroicon-m-briefcase')
                                            ->weight('medium'),
                                            
                                        TextEntry::make('sequences')
                                            ->state(function ($record) {
                                                $nextSequence = $record->sequences()
                                                    ->where('status', 'pending')
                                                    ->orderBy('sequence_number')
                                                    ->first();
                                                $user = $nextSequence?->user;
                                                return $user?->department ?? '';
                                            })
                                            ->label('Department')
                                            ->visible(fn ($state) => !empty($state))
                                            ->icon('heroicon-m-building-office')
                                            ->color('gray'),
                                            
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
                                            ->icon('heroicon-m-arrow-right')
                                            ->size('lg'),
                                            
                                        TextEntry::make('sequences')
                                            ->state(function ($record) {
                                                $nextSequence = $record->sequences()
                                                    ->where('status', 'pending')
                                                    ->orderBy('sequence_number')
                                                    ->first();
                                                $totalSteps = $record->sequences()->count();
                                                $currentStep = $nextSequence?->sequence_number ?? 0;
                                                $completedSteps = $record->sequences()->where('status', 'approved')->count();
                                                
                                                return "$completedSteps of $totalSteps steps completed";
                                            })
                                            ->label('Progress')
                                            ->visible(fn ($state) => !empty($state))
                                            ->badge()
                                            ->color('success')
                                            ->icon('heroicon-m-check-badge'),
                                            
                                        // Visual progress bar
                                        TextEntry::make('approval_progress')
                                            ->state(function ($record) {
                                                $totalSteps = $record->sequences()->count();
                                                $completedSteps = $record->sequences()->whereIn('status', ['approved', 'rejected'])->count();
                                                $progress = $totalSteps > 0 ? round(($completedSteps / $totalSteps) * 100) : 0;
                                                
                                                $progressBar = '';
                                                $progressBar .= '<div class="mt-4 mb-2">';
                                                $progressBar .= '<div class="w-full bg-gray-200 rounded-full h-3 dark:bg-gray-700">';
                                                $progressBar .= '<div class="bg-warning-500 h-3 rounded-full transition-all duration-500 ease-in-out" style="width: ' . $progress . '%"></div>';
                                                $progressBar .= '</div>';
                                                $progressBar .= '</div>';
                                                $progressBar .= '<div class="flex justify-between text-xs font-medium">';
                                                $progressBar .= '<span>' . $progress . '% Complete</span>';
                                                $progressBar .= '<span>' . $completedSteps . '/' . $totalSteps . ' Steps</span>';
                                                $progressBar .= '</div>';
                                                
                                                return $progressBar;
                                            })
                                            ->label('Approval Progress')
                                            ->html()
                                            ->extraAttributes(['class' => 'mt-4 bg-white rounded-lg p-3 shadow-md hover:shadow-lg transition-all duration-300'])
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}