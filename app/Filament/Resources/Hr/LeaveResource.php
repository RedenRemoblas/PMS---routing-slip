<?php

namespace App\Filament\Resources\Hr;

use Filament\Forms;
use Filament\Tables;
use App\Models\Employee;
use App\Models\Hr\Leave;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Hr\LeaveType;
use Illuminate\Support\Carbon;
use App\Models\Hr\LeaveBalance;
use Filament\Resources\Resource;
use Filament\Pages\Actions\Action;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\Group;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use App\Filament\Resources\Hr\LeaveResource\Pages;
use App\Filament\Resources\Hr\CocApplicationResource\RelationManagers\ApprovalStagesRelationManager;

class LeaveResource extends Resource
{
    protected static ?string $model = Leave::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Leave Management';

    protected static ?int $navigationSort = 1;



    public static function form(Form $form): Form
    {

        $user = Auth::user()->load('employee');

        $employeeId = $user->employee->id;

        return $form
            ->schema([
                Section::make('Personal Information')->schema([
                    Forms\Components\DatePicker::make('date_filed')
                        ->default(now())
                        ->disabled(fn() => request()->routeIs('filament.admin.resources.hr.leaves.edit-approval'))
                        ->required(),

                    Forms\Components\TextInput::make('employee_name')
                        ->default(fn() => optional(Auth::user()->employee)->full_name ?? 'Default Name') // Using a closure for lazy evaluation
                        ->disabled()
                        ->label('Employee Name')
                        ->afterStateHydrated(function ($state, $set, $record) {
                            // Accessing the user inside the callback
                            $user = Auth::user();
                            if ($record && $record->employee) {
                                $set('employee_name', $record->employee->full_name);
                            } else {
                                // Fallback to current authenticated user's employee name or another placeholder
                                $set('employee_name', optional($user->employee)->full_name ?? 'No associated employee');
                            }
                        }),



                    Forms\Components\Hidden::make('employee_id')
                        ->default($employeeId)
                        ->required(),
                ])->columns(2),
                // New Section for Leave Balances
                Forms\Components\Section::make('Available Leave Balances')
                    ->schema([
                        Forms\Components\Repeater::make('leave_balances')
                            ->schema([
                                Forms\Components\TextInput::make('leave_name')
                                    ->disabled()
                                    ->label('Leave Type'),
                                Forms\Components\TextInput::make('days_remaining')
                                    ->disabled()
                                    ->label('Days Remaining'),
                                Forms\Components\TextInput::make('days_reserved')
                                    ->disabled()
                                    ->label('Days Reserved'),
                            ])
                            ->columns(4)
                            ->disableItemCreation()
                            ->disableItemDeletion()
                            ->disableItemMovement()
                            ->afterStateHydrated(function ($set, $get) use ($employeeId) {
                                $leaveBalances = LeaveBalance::where('employee_id', $employeeId)
                                    ->with('leaveType')
                                    ->get()
                                    ->map(function ($leaveBalance) {
                                        return [
                                            'leave_name' => $leaveBalance->leaveType->leave_name,
                                            'days_remaining' => $leaveBalance->days_remaining,
                                            'days_reserved' => $leaveBalance->days_reserved,
                                        ];
                                    })->toArray();

                                $set('leave_balances', $leaveBalances);
                            }),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn($get) => $get('employee_id') && !request()->routeIs('filament.admin.resources.hr.leaves.edit-approval')),



                Forms\Components\Section::make('6. DETAILS OF APPLICATION')
                    ->hidden(fn() => request()->routeIs('filament.admin.resources.hr.leaves.edit-approval'))

                    ->schema([

                        Forms\Components\Radio::make('leave_type_id')
                            ->options(function () {
                                return LeaveType::pluck('leave_name', 'id');
                            })
                            ->required()
                            ->label('6.A TYPE OF LEAVE TO BE AVAILED OF'),

                        Group::make()->schema([
                            Forms\Components\Radio::make('details')
                                ->options([
                                    'vacation_within_philippines' => 'Vacation/Special Privilege Leave - Within the Philippines',
                                    'vacation_abroad' => 'Vacation/Special Privilege Leave - Abroad (Specify)',
                                    'sick_in_hospital' => 'Sick Leave - In Hospital (Specify Illness)',
                                    'sick_out_patient' => 'Sick Leave - Out Patient (Specify Illness)',
                                    'special_leave_women' => 'Special Leave Benefits for Women (Specify Illness)',
                                    'study_masters_degree' => 'Study Leave - Completion of Master\'s Degree',
                                    'study_bar_board_review' => 'Study Leave - BAR/Board Examination Review',
                                    'monetization' => 'Monetization of Leave Credits',
                                    'terminal_leave' => 'Terminal Leave',
                                ])
                                ->label('6.B DETAILS OF LEAVE'),

                            Forms\Components\TextInput::make('description')
                                ->maxLength(255)
                                ->label(' DESCRIPTION ')
                                ->helperText('Short description of the details of leave selected in 6.B')
                                ->default(null),

                        ]),

                        Forms\Components\TextInput::make('total_days')
                            ->readOnly()
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->reactive()
                            ->dehydrateStateUsing(function (callable $get) {
                                // Ensure total_days always reflects the latest leaveDetails state
                                $leaveDetails = $get('leaveDetails') ?? [];
                                return collect($leaveDetails)->sum('qty');
                            })
                            ->helperText('This field is automatically updated')
                            ->label('6.C NUMBER OF WORKING DAYS APPLIED FOR'),

                        Forms\Components\Select::make('commutation')
                            ->options([
                                'Not Requested' => 'Not Requested',
                                'Requested' => 'Requested',
                            ])
                            ->default('Not Requested')
                            ->required()
                            ->label('6.D COMMUTATION'),

                        Forms\Components\Hidden::make('leave_status')
                            ->default('pending'),

                    ])
                    ->collapsible()

                    ->extraAttributes(['class' => 'bg-orange-500 p-4 rounded']) // Tailwind classes for peach background
                    ->columns(2),


                Section::make()->schema([

                    //repeater start

                    Repeater::make('leaveDetails')
                        ->relationship('leaveDetails')
                        ->schema([
                            Forms\Components\DatePicker::make('leave_date')
                                ->required()
                                ->label('Leave Date')
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                    $leaveDetails = $get('leaveDetails') ?? [];

                                    foreach ($leaveDetails as &$detail) {
                                        if ($detail['leave_date'] === null) {
                                            $detail['leave_date'] = $state;
                                        }
                                    }

                                    $set('leaveDetails', $leaveDetails);

                                    Log::info('Leave Date Updated', [
                                        'new_leave_date' => $state,
                                        'leaveDetails' => $leaveDetails,
                                    ]);

                                    $totalDays = collect($leaveDetails)->sum('qty');
                                    $set('total_days', $totalDays);

                                    Log::info('Recalculated Total Days after Leave Date Update', [
                                        'total_days' => $totalDays,
                                        'leaveDetails' => $leaveDetails,
                                    ]);
                                }),

                            Forms\Components\Select::make('period')
                                ->options([
                                    'am' => 'AM',
                                    'pm' => 'PM',
                                    'wd' => 'Whole Day',
                                ])
                                ->required()
                                ->default('wd')
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                    // Dynamically set the quantity based on the period
                                    $qty = ($state === 'wd') ? 1.0 : 0.5;

                                    // Update the current item's qty
                                    $set('qty', $qty);

                                    Log::info('Period Updated', [
                                        'new_period' => $state,
                                        'updated_qty' => $qty,
                                    ]);

                                    // Recalculate total days
                                    $leaveDetails = $get('leaveDetails') ?? [];
                                    $totalDays = collect($leaveDetails)->sum('qty');
                                    $set('total_days', $totalDays);

                                    Log::info('Recalculated Total Days after Period Update', [
                                        'total_days' => $totalDays,
                                    ]);
                                }),



                            Forms\Components\TextInput::make('qty')
                                ->numeric()
                                ->required()
                                ->default(1.0)
                                ->readOnly()
                                ->label('Quantity')
                                ->afterStateHydrated(function ($state, callable $get) {
                                    $leaveDetails = $get('leaveDetails') ?? [];

                                    Log::info('Qty Field Hydrated', [
                                        'qty' => $state,
                                        'leaveDetails' => $leaveDetails,
                                    ]);
                                }),
                        ])
                        ->columns(3)
                        ->label('Leave Details')
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $leaveDetails = $state ?? [];
                            $totalDays = collect($leaveDetails)->sum('qty');
                            $set('total_days', $totalDays);

                            Log::info('Repeater Updated', [
                                'total_days' => $totalDays,
                                'leaveDetails' => $leaveDetails,
                            ]);
                        })
                        ->afterStateHydrated(function ($state, callable $set) {
                            if (!$state) {
                                $set('leaveDetails', []);
                            }

                            Log::info('Repeater Hydrated', [
                                'leaveDetails' => $state,
                            ]);
                        })
                        ->createItemButtonLabel('Add More Leave Detail'),



                    ///repeater end


                ])->columnSpanFull()
                    ->collapsible()
                    ->hidden(fn() => request()->routeIs('filament.admin.resources.hr.leaves.edit-approval'))
                    ->description("Please specify the dates for the applied leave in this section, ensuring all days within the leave application period are included."),

            ])->columns(2);
    }
    protected static function recalculateTotalDays(callable $get, callable $set)
    {
        $leaveDetails = $get('leaveDetails') ?? [];
        $totalDays = collect($leaveDetails)->sum('qty');
        $set('total_days', $totalDays);

        Log::info('Recalculated Total Days', ['total_days' => $totalDays, 'leaveDetails' => $leaveDetails]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date_filed')->date()->sortable(),
                Tables\Columns\TextColumn::make('employee.full_name')->label('Employee')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('leaveType.leave_name')->label('Leave Type')->sortable()->searchable(),

                Tables\Columns\TextColumn::make('total_days')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('leave_status')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

            ]);
    }


    public static function getRelations(): array
    {


        $user = Auth::user(); // Get the current authenticated user



        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaves::route('/'),
            'create' => Pages\CreateLeave::route('/create'),

            'view' => Pages\ViewLeave::route('/{record}'),
        ];
    }

    protected function handleDeleteLeave(Leave $leave): void
    {
        $employeeId = $leave->employee_id;
        $leaveTypeId = $leave->leave_type_id;
        $totalDaysReserved = $leave->leaveDetails()->sum('qty');

        try {
            // Deduct the reserved days
            Leave::deductReservedLeaveDays($employeeId, $leaveTypeId, $totalDaysReserved);
            Log::info("Successfully deducted {$totalDaysReserved} reserved days for employee ID {$employeeId}, leave type ID {$leaveTypeId}.");
        } catch (ValidationException $e) {
            Log::error("Error deducting reserved days for employee ID {$employeeId}, leave type ID {$leaveTypeId}: " . $e->getMessage());
            throw $e;
        }
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        // Ensure the user has an employee relationship and a valid employee number
        return $user?->employee !== null && $user->employee->employee_no !== null;
    }
}
