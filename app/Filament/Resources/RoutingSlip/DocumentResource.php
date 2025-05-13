<?php

namespace App\Filament\Resources\RoutingSlip;

use App\Filament\Resources\RoutingSlip\DocumentResource\Pages;
use App\Models\Document\RoutingSlip;
use App\Models\User;
use App\Models\Employee;
use App\Models\Setup\Division;
use App\Models\Setup\Position;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Support\Colors\Color;

class DocumentResource extends Resource
{
    protected static ?string $model = RoutingSlip::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationGroup = 'Routing Slip';

    protected static ?string $navigationLabel = 'Document';

    protected static ?string $modelLabel = 'Document';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Document Information')
                    ->description('Enter the basic information about the document')
                    ->icon('heroicon-o-document')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('id')
                            ->label('Tracking No.')
                            ->prefix('RS-')
                            ->disabled()
                            ->visible(fn ($record) => $record !== null)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('document_type')
                            ->label('Document Type')
                            ->options([
                                'digital' => 'Digital Copy',
                                'physical' => 'Physical Copy',
                            ])
                            ->default('digital')
                            ->required()
                            ->live()
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('files')
                            ->multiple()
                            ->directory('routing-slips')
                            ->preserveFilenames()
                            ->required(fn (Forms\Get $get): bool => $get('document_type') === 'digital')
                            ->visible(fn (Forms\Get $get): bool => $get('document_type') === 'digital')
                            ->columnSpanFull()
                            ->downloadable()
                            ->openable()
                            ->reorderable()
                            ->disk('public')
                            ->visibility('public')
                            ->maxSize(1024 * 1024 * 1024) // 1GB (effectively no limit)
                            ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                            ->helperText('Accepted files: PDF, Images, Word documents. Max size: 5MB'),
                        Forms\Components\Textarea::make('remarks')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Routing Sequence')
                    ->description('Define the sequence of users who need to review this document')
                    ->icon('heroicon-o-user-group')
                    ->schema([
                        Forms\Components\Repeater::make('sequences')
                            ->relationship('sequences')
                            ->schema([
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Select::make('user_id')
                                            ->label('Name')
                                            ->relationship('user', 'name')
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->native(false)
                                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                $user = User::find($state);
                                                if ($user) {
                                                    $employee = Employee::where('user_id', $user->id)->first();
                                                    if ($employee) {
                                                        $division = Division::find($employee->division_id);
                                                        $position = Position::find($employee->position_id);
                                                        $set('division', $division ? $division->name : '');
                                                        $set('position', $position ? $position->name : '');
                                                    }
                                                }
                                            }),
                                        Forms\Components\TextInput::make('division')
                                            ->label('Division')
                                            ->disabled()
                                            ->dehydrated(false),
                                        Forms\Components\TextInput::make('position')
                                            ->label('Position')
                                            ->disabled()
                                            ->dehydrated(false),
                                        Forms\Components\TextInput::make('sequence_number')
                                            ->label('Sequence')
                                            ->numeric()
                                            ->required()
                                            ->default(fn (Forms\Get $get): int => 
                                                count($get('../../sequences') ?? []) + 1
                                            )
                                            ->disabled(),
                                    ]),
                            ])
                            ->orderColumn('sequence_number')
                            ->defaultItems(1)
                            ->addActionLabel('Add User to Sequence')
                            ->reorderableWithButtons()
                            ->itemLabel(fn (array $state): ?string => 
                                $state['admin_type'] ?? null
                                    ? User::find($state['user_id'])?->name . ' (' . ucfirst($state['admin_type']) . ')'
                                    : null
                            )
                            ->required()
                            ->columns(1),
                    ]),
                    
                Forms\Components\Section::make('Carbon Copy (CC) Recipients')
                    ->description('Add people who should receive a copy of this document')
                    ->icon('heroicon-o-user-group')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Repeater::make('ccRecipients')
                            ->relationship('ccRecipients')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Select::make('user_id')
                                            ->label('Name')
                                            ->relationship('user', 'name')
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->native(false)
                                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                $user = User::find($state);
                                                if ($user) {
                                                    $employee = Employee::where('user_id', $user->id)->first();
                                                    if ($employee) {
                                                        $division = Division::find($employee->division_id);
                                                        $position = Position::find($employee->position_id);
                                                        $set('division', $division ? $division->name : '');
                                                        $set('position', $position ? $position->name : '');
                                                        $set('email', $user->email);
                                                        $set('name', $user->name); // Set the name field from the user
                                                    }
                                                }
                                            }),
                                        Forms\Components\TextInput::make('position')
                                            ->label('Position')
                                            ->disabled()
                                            ->dehydrated(true)
                                            ->required(),
                                        Forms\Components\TextInput::make('division')
                                            ->label('Division')
                                            ->disabled()
                                            ->dehydrated(true)
                                            ->required(),
                                        Forms\Components\TextInput::make('email')
                                            ->label('Email')
                                            ->email()
                                            ->disabled()
                                            ->dehydrated(true)
                                            ->required(),
                                        Forms\Components\Hidden::make('name')
                                            ->dehydrated(true)
                                            ->required(),
                                    ]),
                            ])
                            ->itemLabel(fn (array $state): ?string => $state['user_id'] ? User::find($state['user_id'])?->name : null)
                            ->addActionLabel('Add CC Recipient')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->columns(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
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
                        default => 'gray',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->url(fn (RoutingSlip $record): string => static::getUrl('view', ['record' => $record]))
                    ->icon('heroicon-o-eye'),
                Tables\Actions\EditAction::make()
                    ->visible(fn (RoutingSlip $record): bool => auth()->id() === $record->created_by),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (RoutingSlip $record): bool => auth()->id() === $record->created_by),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListDocuments::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'view' => Pages\ViewDocument::route('/{record}'),
            'edit' => Pages\EditDocument::route('/{record}/edit'),
            'workflow' => Pages\DocumentWorkflow::route('/{record}/workflow'),
        ];
    }
}
