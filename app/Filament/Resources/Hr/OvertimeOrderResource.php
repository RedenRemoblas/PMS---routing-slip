<?php

namespace App\Filament\Resources\Hr;

use Filament\Forms;
use Filament\Tables;
use App\Models\Employee;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Hr\OvertimeOrder;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;

use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Infolists\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\FileUpload;

use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use App\Filament\Resources\Hr\OvertimeOrderResource\Pages;
use App\Filament\Resources\Hr\OvertimeOrderResource\RelationManagers\ApprovalStagesRelationManager;

class OvertimeOrderResource extends Resource
{
    protected static ?string $model = OvertimeOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-top-right-on-square';

    protected static ?string $navigationGroup = 'Overtime Management';

    public static function form(Form $form): Form
    {



        return $form
            ->schema([
                Section::make('Overtime Order Information')
                    ->schema([


                        Forms\Components\Grid::make(2)->schema([


                            Forms\Components\Hidden::make('date_filed')
                                ->default(now())
                                ->required(),


                            Forms\Components\TextInput::make('status')
                                ->label('Status')
                                ->disabled()
                                ->default('Pending'),
                        ]),

                        Forms\Components\Textarea::make('purpose')
                            ->required()
                            ->label('Purpose of Overtime')
                            ->helperText("Include in the purpose the details such as when, where and why.")
                            ->columnSpanFull(),
                    ]),



                Section::make('Overtime Details')
                    ->schema([
                        Repeater::make('overtime_order_details')
                            ->relationship('details')
                            ->label('List of Employees')
                            ->schema([
                                Forms\Components\Grid::make(4)->schema([


                                    Forms\Components\Select::make('employee_id')
                                        ->label('Employee')
                                        ->options(function () {
                                            return Employee::all()->pluck('full_name', 'id');
                                        })
                                        ->searchable()
                                        ->required()
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            $employee = \App\Models\Employee::find($state);
                                            if ($employee) {
                                                $set('position', $employee->position->name ?? '');
                                                $set('division', $employee->division->name ?? '');
                                            }
                                        })
                                        ->columnSpan(1),




                                    Forms\Components\TextInput::make('position')
                                        ->label('Position')
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('division')
                                        ->label('Division')
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('hours_rendered')
                                        ->numeric()
                                        ->minValue(2)
                                        ->label('Hours Rendered')
                                        ->required()
                                        ->columnSpan(1),
                                ]),
                            ])
                            ->required() // Ensures the repeater is not empty
                            ->addActionLabel('Add Employee')
                            ->columns(1)
                            ->columnSpan('full'),
                    ])
                    ->collapsible()
                    ->collapsed(false),
                Section::make('Supporting Documents')->schema([
                    Repeater::make('documents')
                        ->label('Supporting Documents')
                        ->relationship('documents') // Define the relationship
                        ->schema([
                            FileUpload::make('file_path')
                                ->label('File')
                                ->disk('public') // Specify the disk
                                ->directory('overtime_documents') // Directory for uploaded files
                                ->acceptedFileTypes(['application/pdf']) // Only allow PDFs
                                ->maxSize(1024 * 1024 * 1024) // 1GB (effectively no limit)
                ->multiple()

                                ->required() // Each document is required
                                ->getUploadedFileNameForStorageUsing(function (\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file, $record) {
                                    // Safely access record details via the passed $record
                                    $overtimeNo = $record->id ?? '0000';
                                    $timestamp = now()->format('YmdHis'); // Current timestamp
                                    $filename = "leave_application_{$overtimeNo}_{$timestamp}.pdf";


                                    return $filename;
                                })
                        ])
                        ->addActionLabel('Add Document')
                        ->columns(1),

                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('Overtime ID')->sortable()->searchable(),
                TextColumn::make('creator.fullname')->label('Created By')->sortable()->searchable(),
                TextColumn::make('date_filed')->label('Date Filed')->date(),
                TextColumn::make('status')->label('Status')->sortable()->searchable(),
                TextColumn::make('purpose')->label('Purpose')->sortable()->searchable(),
                TextColumn::make('documents')
                    ->label('Supporting Documents')
                    ->formatStateUsing(function ($record) {
                        return $record->documents->map(function ($doc) {
                            return "<a href='" . asset('storage/' . $doc->file_path) . "' target='_blank'>" . ($doc->file_name ?? 'Download') . "</a>";
                        })->join('<br>');
                    })
                    ->html(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->hidden(fn(OvertimeOrder $record) => $record->status !== 'Pending'),

                Tables\Actions\ViewAction::make()
                    ->url(fn($record) => static::getUrl('view', ['record' => $record->getKey()])),
            ])
            ->bulkActions([]);
    }




    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([


                Fieldset::make('Overtime Request Description')
                    ->schema([
                        TextEntry::make('status')
                            ->badge(fn($state) => [
                                'approved' => 'success',
                                'pending' => 'warning',
                                'rejected' => 'danger',
                            ][$state] ?? 'secondary')
                            ->label('Status'),

                        TextEntry::make('date_filed')
                            ->label('Date Filed'),

                        TextEntry::make('purpose')
                            ->label('Purpose of Overtime')
                            ->columnSpan(2),
                    ]),


                RepeatableEntry::make('details')
                    ->label('List of Participating Employees')
                    ->schema([
                        Grid::make(3) // Use 3 columns to place all fields in one row
                            ->schema([
                                TextEntry::make('employee.full_name')
                                    ->label('Employee Name')
                                    ->state(fn($record) => $record->employee->full_name ?? 'N/A'),

                                TextEntry::make('position')
                                    ->label('Position')
                                    ->state(fn($record) => $record->position ?? 'N/A'),

                                TextEntry::make('division')
                                    ->label('Division')
                                    ->state(fn($record) => $record->division ?? 'N/A'),
                            ]),
                    ])
                    ->columnSpan(2), // Adjust layout if necessary

                RepeatableEntry::make('documents')
                    ->label('Supporting Documents')
                    ->schema([

                        TextEntry::make('file_name')
                            ->label('Document Name')
                            ->state(fn($record) => $record->file_name ?? 'Unnamed Document'),


                        TextEntry::make('file_path')
                            ->badge()
                            ->label('Download Link')
                            ->state(fn($record) => '<a href="' . Storage::url($record->file_path) . '" target="_blank" class="text-blue-500 hover:underline">Download</a>')
                            ->html(), // Enable HTML rendering for clickable links

                    ])
                    ->columns(2) // Two columns: one for the document name and one for the link
                    ->columnSpan(2) // Adjust layout if necessary
            ]);
    }


    public static function getRelations(): array
    {
        return [
            ApprovalStagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOvertimeOrders::route('/'),
            'create' => Pages\CreateOvertimeOrder::route('/create'),
            'edit' => Pages\EditOvertimeOrder::route('/{record}/edit'),
            'view' => Pages\ViewOvertimeOrder::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        // Admin users should see all overtime orders
        if ($user->hasRole('admin')) {
            return parent::getEloquentQuery();
        }

        // For regular users (non-admin), filter the overtime orders they are involved in
        if ($user->employee) {
            return parent::getEloquentQuery()
                ->where(function ($query) use ($user) {
                    $query->where('created_by', $user->employee->id) // The user created the overtime order
                        ->orWhereHas('details', function ($detailsQuery) use ($user) {
                            $detailsQuery->where('employee_id', $user->employee->id); // The user is listed in the overtime details
                        });
                });
        }

        // If the user is not associated with an employee, return an empty query
        return parent::getEloquentQuery()->whereRaw('1 = 0');
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        // Ensure the user has an employee relationship and a valid employee number
        return $user?->employee !== null && $user->employee->employee_no !== null;
    }
}
