<?php

namespace App\Filament\Resources\Travel;

use App\Filament\Resources\Travel\TravelOrderResource\Pages;
use App\Filament\Resources\Travel\TravelOrderResource\RelationManagers\ApprovalStagesRelationManager;
use App\Models\Employee;
use App\Models\Setup\Place;
use App\Models\Setup\Project;
use App\Models\Travel\TravelOrder;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TravelOrderResource extends Resource
{
    protected static ?string $model = TravelOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected ?string $subheading = 'Custom Page Subheading';

    protected static ?string $navigationGroup = 'Travel Order';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Travel Order Information')
                    ->schema([

                        Forms\Components\Hidden::make('employee_id')
                            ->default(fn() => Auth::user()->employee->id)
                            ->required(),

                        Grid::make(4)->schema([

                            Forms\Components\DatePicker::make('inclusive_start_date')
                                ->required()
                                ->columns(1)
                                ->label('Start Date'),

                            Forms\Components\DatePicker::make('inclusive_end_date')
                                ->required()
                                ->columns(1)
                                ->label('End Date'),

                            Forms\Components\TextInput::make('funding_type')
                                ->label('Funding Type')
                                ->required()
                                ->autocomplete('off') // Disable browser autocomplete
                                ->datalist(Project::pluck('name', 'id')->toArray()) // Custom attribute for HTML5 datalist
                                ->reactive(),

                            Forms\Components\TextInput::make('official_vehicle')
                                ->columns(1)
                                ->label('Official Vehicle'),

                            Forms\Components\TextInput::make('place_of_origin')
                                ->label('Place of Origin')
                                ->autocomplete('off')
                                ->datalist(Place::pluck('name', 'id')->toArray()) // Custom attribute for HTML5 datalist
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $origin = Place::where('name', $state)->first();
                                    $destination = Place::where('name', $get('destination'))->first();

                                    if ($origin && $destination) {
                                        $distance = Place::calculateDistance(
                                            $origin->latitude,
                                            $origin->longitude,
                                            $destination->latitude,
                                            $destination->longitude
                                        );
                                        $set('farthest_distance', $distance);
                                    }
                                }),

                            Forms\Components\TextInput::make('destination')
                                ->label('Destination')
                                ->autocomplete('off')
                                ->datalist(Place::pluck('name', 'id')->toArray()) // Custom attribute for HTML5 datalist
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $origin = Place::where('name', $get('place_of_origin'))->first();
                                    $destination = Place::where('name', $state)->first();

                                    if ($origin && $destination) {
                                        $distance = Place::calculateDistance(
                                            $origin->latitude,
                                            $origin->longitude,
                                            $destination->latitude,
                                            $destination->longitude
                                        );
                                        $set('farthest_distance', $distance);
                                    }
                                }),

                            Forms\Components\TextInput::make('status')
                                ->label('Status')
                                ->columns(1)
                                ->disabled()
                                ->default('Pending'),

                            Forms\Components\TextInput::make('farthest_distance')
                                ->numeric()
                                ->columns(1)
                                ->label('Aerial Distance (Km)')
                                ->extraInputAttributes(['readonly' => true]),

                            Forms\Components\TextInput::make('purpose')
                                ->required()
                                ->columnSpanFull()
                                ->label('Purpose'),
                        ]),
                    ])
                    ->extraAttributes(['style' => 'background-color:#ccc']),

                Section::make('Personnel to Travel')
                    ->schema([
                        Repeater::make('travel_order_details')
                            ->relationship('details')
                            ->label('List of Personnel')
                            ->schema([
                                Grid::make(4)->schema([


                                    Forms\Components\Select::make('employee_id')
                                        ->label('Employee')
                                        ->options(Employee::all()->mapWithKeys(function ($employee) {
                                            return [$employee->id => $employee->full_name];
                                        }))
                                        ->required()
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            $employee = Employee::find($state);
                                            if ($employee) {
                                                $set('position', $employee->position->name ?? '');
                                                $set('division', $employee->division->name ?? '');
                                            }
                                        })
                                        ->columnSpan(2),

                                    Forms\Components\TextInput::make('position')
                                        ->label('Position')
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('division')
                                        ->label('Division')
                                        ->columnSpan(1),
                                ]),
                            ])
                            ->createItemButtonLabel('Add Employee')
                            ->columns(1)
                            ->extraAttributes(['class' => 'space-y-2'])
                            ->columnSpan('full'),
                    ])->extraAttributes(['style' => 'background-color:#ccc'])
                    ->collapsible()
                    ->collapsed(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('Travel ID')->sortable()->searchable(),
                TextColumn::make('employee.fullname')->label('Created By')->sortable()->searchable(),
                TextColumn::make('created_at')->label('Date Created')->date(),
                TextColumn::make('inclusive_start_date')->label('Travel  Date')->date(),
                TextColumn::make('inclusive_end_date')->label('Return Date')->date(),

                TextColumn::make('status')->label('Status')->sortable()->searchable(),
                TextColumn::make('place_of_origin')->label('Place of Origin')->sortable()->searchable(),
                TextColumn::make('destination')->label('Destination')->sortable()->searchable(),
                TextColumn::make('purpose')->label('Purpose')->sortable()->searchable(),
                TextColumn::make('farthest_distance')->label('Farthest Distance')->sortable()->searchable(),
                TextColumn::make('funding_type')
                    ->label('Funding Type')
                    ->sortable()
                    ->searchable()
                    ->getStateUsing(fn(TravelOrder $record) => $record->funding_type ?? 'N/A'),

            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->hidden(fn(TravelOrder $record) => $record->status !== 'Pending'),

                Tables\Actions\ViewAction::make()
                    ->url(fn($record) => static::getUrl('view', ['record' => $record->getKey()])),

            ])
            ->bulkActions([
                //Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListTravelOrders::route('/'),
            'create' => Pages\CreateTravelOrder::route('/create'),
            'edit' => Pages\EditTravelOrder::route('/{record}/edit'),
            'view' => Pages\ViewTravelOrder::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        // Admin users should see all travel orders
        if ($user->hasRole('admin')) {
            return parent::getEloquentQuery();
        }

        // For regular users (non-admin), filter the travel orders they are involved in
        if ($user->employee) {
            return parent::getEloquentQuery()
                ->where(function ($query) use ($user) {
                    $query->where('employee_id', $user->employee->id) // The user created the travel order
                        ->orWhereHas('details', function ($detailsQuery) use ($user) {
                            $detailsQuery->where('employee_id', $user->employee->id); // The user is listed in the travel details
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
