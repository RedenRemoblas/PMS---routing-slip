<?php

namespace App\Filament\Resources\Travel;

use App\Filament\Resources\Travel\TravelOrderApprovalResource\Pages;
use App\Filament\Resources\Travel\TravelOrderResource\RelationManagers\ApprovalStagesRelationManager;
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
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TravelOrderApprovalResource extends Resource
{
    protected static ?string $model = TravelOrder::class;

    protected static ?string $navigationIcon = 'heroicon-s-truck';

    protected static ?string $navigationGroup = 'Travel Order';

    protected static ?string $navigationLabel = 'Review Travel Orders';

    protected static ?int $navigationSort = 2;

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
                                        ->relationship('employee', 'lastname')
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
                TextColumn::make('employee.fullname')
                    ->label('Created By')
                    ->sortable()
                    ->searchable(
                        query: fn(Builder $query, string $search) =>
                        $query->orWhereHas(
                            'employee',
                            fn($q) =>
                            $q->whereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", ["%$search%"])
                        )
                    ),

                TextColumn::make('created_at')->label('Date Created')->date(),

                TextColumn::make('inclusive_start_date')->label('Travel Date')->date(),
                //  TextColumn::make('inclusive_end_date')->label('Return Date')->date(),

                TextColumn::make('status')->label('Status')->sortable()->searchable()->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Pending' => 'gray',
                        'Approved' => 'success',
                        'Rejected' => 'danger',
                        'Locked' => 'warning',
                        default => 'secondary'
                    }),
                //   TextColumn::make('place_of_origin')->label('Place of Origin')->sortable()->searchable(),
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

                //   Tables\Actions\ViewAction::make()
                //      ->url(fn($record) => static::getUrl('view', ['record' => $record->getKey()])),

                Tables\Actions\ViewAction::make(),


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
            'index' => Pages\ListTravelOrderApprovals::route('/'),

            'view' => Pages\ViewTravelOrderApprovals::route('/{record}'),
        ];
    }

    /*
     fetch only the travel orders that are pending for approval by the current user
    */

    /*  public static function getEloquentQuery(): Builder
    {

        $user = Auth::user();

        return TravelOrder::whereIn('id', function ($query) use ($user) {
            $query->select('travel_order_id')
                ->from('travel_approval_stages as tas')
                ->where('tas.employee_id', $user->employee->id)
                //  ->where('tas.status', 'pending')
                ->where('tas.sequence', function ($subQuery) {
                    $subQuery->selectRaw('MIN(tas2.sequence)')
                        ->from('travel_approval_stages as tas2')
                        ->whereColumn('tas2.travel_order_id', 'tas.travel_order_id');
                    // ->where('tas2.status', 'pending');
                });
        });
    }
*/

    /*  public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        return TravelOrder::whereIn('id', function ($query) use ($user) {
            $query->select('travel_order_id')
                ->from('travel_approval_stages as tas')
                ->where('tas.employee_id', $user->employee->id)
                //   ->where('tas.status', 'pending') // Only show pending approvals
                ->where('tas.sequence', function ($subQuery) {
                    $subQuery->selectRaw('MIN(tas2.sequence)')
                        ->from('travel_approval_stages as tas2')
                        ->whereColumn('tas2.travel_order_id', 'tas.travel_order_id');
                    //  ->where('tas2.status', 'pending'); // Ensure it selects the next pending approver
                });
        });
    }
*/

    /*  public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        return TravelOrder::whereIn('id', function ($query) use ($user) {
            $query->select('travel_order_id')
                ->from('travel_approval_stages as tas')
                ->where('tas.employee_id', $user->employee->id)
                ->where(function ($subQuery) {
                    // Show if the user is the next approver
                    $subQuery->where('tas.status', 'pending')
                        ->where('tas.sequence', function ($minSequenceQuery) {
                            $minSequenceQuery->selectRaw('MIN(tas2.sequence)')
                                ->from('travel_approval_stages as tas2')
                                ->whereColumn('tas2.travel_order_id', 'tas.travel_order_id')
                                ->where('tas2.status', 'pending'); // Ensure it's the next pending one
                        })
                        // OR show if the user has already approved
                        ->orWhere('tas.status', 'approved')
                        ->orWhere('tas.status', 'rejected');
                });
        });
    }

    */

    public static function getEloquentQuery(): Builder
    {
        $userId = Auth::id(); // Get the authenticated user's ID
        $employeeId = Auth::user()->employee->id; // Get employee ID

        return TravelOrder::whereIn('id', function ($query) use ($employeeId) {
            $query->select('travel_order_id')
                ->from('travel_approval_stages as tas')
                ->where('tas.employee_id', $employeeId)
                ->where(function ($subQuery) use ($employeeId) {
                    // 1. Show if the user is the next pending approver
                    $subQuery->where('tas.status', 'pending')
                        ->where('tas.sequence', function ($minSequenceQuery) {
                            $minSequenceQuery->selectRaw('MIN(tas2.sequence)')
                                ->from('travel_approval_stages as tas2')
                                ->whereColumn('tas2.travel_order_id', 'tas.travel_order_id')
                                ->where('tas2.status', 'pending'); // The next pending one
                        })
                        // 2. OR Show if the user has already approved this record
                        ->orWhereRaw('EXISTS (
                        SELECT 1 FROM travel_approval_stages AS tas3
                        WHERE tas3.travel_order_id = tas.travel_order_id
                        AND tas3.employee_id = ?
                        AND ((tas3.status = "approved") or (tas3.status = "endorsed"))
                    )', [$employeeId]); // Ensuring past approvers can still see it
                });
        });
    }



    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        // Ensure the user has an employee relationship and a valid employee number
        return $user?->employee !== null && $user->employee->employee_no !== null;
    }
}
