<?php

namespace App\Filament\Resources\RoutingSlip;

use App\Filament\Resources\RoutingSlip\ReviewDocumentResource\Pages;
use App\Models\Document\RoutingSlip;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Colors\Color;
use Filament\SpatieLaravelMediaLibrary\Forms\Components\SpatieMediaLibraryFileUpload;

class ReviewDocumentResource extends Resource
{
    protected static ?string $model = RoutingSlip::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    
    protected static ?string $navigationGroup = 'Routing Slip';

    protected static ?string $navigationLabel = 'Document Review';

    protected static ?string $modelLabel = 'Document Review';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Document Information')
                    ->description('View the document information')
                    ->icon('heroicon-o-document')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('id')
                            ->label('Tracking No.')
                            ->prefix('RS-')
                            ->disabled()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('title')
                            ->disabled(fn (RoutingSlip $record): bool => $record->status === 'finalized')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('remarks')
                            ->disabled(fn (RoutingSlip $record): bool => $record->status === 'finalized')
                            ->columnSpanFull(),
                        Forms\Components\Placeholder::make('status')
                            ->content(fn (RoutingSlip $record): string => ucfirst($record->status))
                            ->columnSpanFull(),
                        Forms\Components\Placeholder::make('created_at')
                            ->label('Created At')
                            ->content(fn (RoutingSlip $record): string => $record->created_at->format('M d, Y H:i'))
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Signed Documents')
                    ->description('View and download signed documents')
                    ->icon('heroicon-o-paper-clip')
                    ->schema([
                        Forms\Components\Repeater::make('files')
                            ->relationship('files', fn ($query) => $query->where('file_type', 'supporting'))
                            ->schema([
                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\SpatieMediaLibraryFileUpload::make('file_path')
                                            ->label('Upload File')
                                            ->collection('supporting-documents')
                                            ->preserveFilenames()
                                            ->maxSize(1024 * 1024 * 1024) // Setting to 1GB (effectively no limit)
                                            ->acceptedFileTypes([
                                                'application/pdf',
                                                'image/jpeg',
                                                'image/png',
                                                'application/msword',
                                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                                'application/vnd.ms-excel',
                                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                                'text/plain',
                                                'text/csv',
                                                'application/zip'
                                            ])
                                            ->downloadable()
                                            ->openable()
                                            ->required(),
                                        Forms\Components\TextInput::make('file_name')
                                            ->label('Document Name')
                                            ->required()
                                            ->maxLength(255)
                                            ->disabled(fn ($record): bool => $record instanceof \App\Models\Document\RoutingSlip ? $record->status === 'finalized' : $record->routingSlip->status === 'finalized'),
                                        Forms\Components\Hidden::make('uploaded_by')
                                            ->default(auth()->id()),
                                        Forms\Components\Hidden::make('file_type')
                                            ->default('supporting'),
                                    ])
                                    ->columns(2),
                                Forms\Components\Placeholder::make('created_at')
                                    ->label('Upload Date')
                                    ->content(fn ($record): string => $record->created_at ? $record->created_at->format('M d, Y H:i') : '-')
                                    ->columnSpanFull(),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel('Add Document')
                            ->disabled(fn ($record): bool => $record instanceof \App\Models\Document\RoutingSlip ? $record->status === 'finalized' : $record->routingSlip->status === 'finalized')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Approval Sequence')
                    ->description('View the approval sequence')
                    ->icon('heroicon-o-user-group')
                    ->schema([
                        Forms\Components\Repeater::make('sequences')
                            ->relationship('sequences')
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->label('Name')
                                    ->relationship('user', 'name')
                                    ->disabled(fn ($record): bool => $record instanceof \App\Models\Document\RoutingSlip ? $record->status === 'finalized' : $record->routingSlip->status === 'finalized'),
                                Forms\Components\TextInput::make('status')
                                    ->disabled(fn ($record): bool => $record instanceof \App\Models\Document\RoutingSlip ? $record->status === 'finalized' : $record->routingSlip->status === 'finalized'),
                                Forms\Components\Textarea::make('remarks')
                                    ->disabled(fn ($record): bool => $record instanceof \App\Models\Document\RoutingSlip ? $record->status === 'finalized' : $record->routingSlip->status === 'finalized'),
                                Forms\Components\TextInput::make('sequence_number')
                                    ->label('Sequence')
                                    ->disabled(fn ($record): bool => $record instanceof \App\Models\Document\RoutingSlip ? $record->status === 'finalized' : $record->routingSlip->status === 'finalized'),
                            ])
                            ->columns(2)
                            ->disabled(fn ($record): bool => $record instanceof \App\Models\Document\RoutingSlip ? $record->status === 'finalized' : $record->routingSlip->status === 'finalized')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        // Set a higher execution time limit for this resource
        set_time_limit(300); // 5 minutes
        
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Tracking No.')
                    ->prefix('RS-')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'finalized' => 'primary',
                        default => 'gray',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('approval_sequence')
                    ->label('Your Approval Status')
                    ->state(function (RoutingSlip $record): string {
                        // Get the current user's sequence
                        $userSequence = $record->sequences()
                            ->where('user_id', auth()->id())
                            ->first();
                            
                        // User must be in sequence to see this document
                        // This check is redundant as we filter in getEloquentQuery
                        // but kept for safety
                        if (!$userSequence) {
                            return 'Error: Not in sequence';
                        }
                        
                        // Get the current active sequence (lowest pending sequence)
                        $currentSequence = $record->sequences()
                            ->where('status', 'pending')
                            ->orderBy('sequence_number')
                            ->first();
                            
                        if (!$currentSequence) {
                            return 'All approved';
                        }
                        
                        if ($userSequence->id === $currentSequence->id) {
                            return 'Your turn to review';
                        }
                        
                        if ($userSequence->sequence_number < $currentSequence->sequence_number) {
                            return 'Already reviewed';
                        }
                        
                        return 'Waiting for previous approvers';
                    })
                    ->badge()
                    ->color(function (string $state): string {
                        return match($state) {
                            'Your turn to review' => 'success',
                            'Already reviewed' => 'primary',
                            'Waiting for previous approvers' => 'warning',
                            default => 'gray',
                        };
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'finalized' => 'Finalized',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (RoutingSlip $record): bool => 
                        ($record->status !== 'finalized') && 
                        (auth()->user()->hasRole('admin') || $record->created_by === auth()->id()) &&
                        // Ensure approvers cannot see edit button
                        !($record->sequences()->where('user_id', auth()->id())->where('user_id', '!=', $record->created_by)->exists())
                    ),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (RoutingSlip $record): bool => 
                        ($record->status !== 'finalized') && 
                        (auth()->user()->hasRole('admin') || $record->created_by === auth()->id()) &&
                        // Ensure approvers cannot see delete button
                        !($record->sequences()->where('user_id', auth()->id())->where('user_id', '!=', $record->created_by)->exists())
                    ),
            ])
            ->bulkActions([])
            ->paginated([
                'pageName' => 'page',
                'perPage' => 10, // Limit records per page
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReviewDocuments::route('/'),
            'view' => Pages\ViewReviewDocument::route('/{record}'),
            'edit' => Pages\EditReviewDocument::route('/{record}/edit'),
            'review' => Pages\ReviewDocument::route('/{record}/review'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        // Set a higher execution time limit for this resource
        set_time_limit(300); // 5 minutes
        
        // Get base query
        $query = parent::getEloquentQuery();

        // Only show documents that are in pending status (locked by creator)
        // This ensures documents aren't visible to approvers until creator locks them
        $query->where('status', '!=', 'created');
        
        // Eager load relationships to reduce database queries
        $query->with(['creator', 'sequences', 'files']);

        // Check if we're viewing a specific record
        $routeRecord = request()->route('record');
        
        // If we're on the index page (not viewing a specific record)
        if (!$routeRecord) {
            // Exclude finalized documents for all users on index page only
            $query->where('status', '!=', 'finalized');
            
            // For all users (including admins), only show documents where they are in the approval sequence
            // This ensures no documents with "Not in sequence" status are displayed
            $query->whereHas('sequences', function (Builder $query) {
                $query->where('user_id', auth()->id());
            });
            
            // For non-admin users, apply additional filters
            if (!auth()->user()->hasRole('admin')) {
                // Additionally, only show documents where:
                // 1. The document is not approved
                // 2. The user is the next approver (if their status is pending and they have the lowest sequence number)
                $query->where(function (Builder $query) {
                    // Show documents where user is the next approver
                    $query->whereHas('sequences', function (Builder $subQuery) {
                        $subQuery->where('user_id', auth()->id())
                               ->where('status', 'pending');
                               // Removed the restrictive whereRaw condition that was filtering out some documents
                    });
                    
                    // Or show documents where user has already reviewed (for reference)
                    $query->orWhereHas('sequences', function (Builder $subQuery) {
                        $subQuery->where('user_id', auth()->id())
                               ->whereIn('status', ['approved', 'rejected']);
                    });
                    
                    // Or show documents where user is in the sequence but not yet their turn
                    $query->orWhereHas('sequences', function (Builder $subQuery) {
                        $subQuery->where('user_id', auth()->id());
                    });
                })
                ->where('status', '!=', 'approved');
            }
        }
        // If viewing a specific record, still ensure the user is in the sequence
        // unless they are an admin or the creator of the document
        else {
            $record = RoutingSlip::find($routeRecord);
            if ($record && !auth()->user()->hasRole('admin') && $record->created_by !== auth()->id()) {
                $query->whereHas('sequences', function (Builder $query) {
                    $query->where('user_id', auth()->id());
                });
            }
        }

        return $query;
    }
}