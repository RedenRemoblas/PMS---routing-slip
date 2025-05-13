<?php

namespace App\Filament\Resources\Hr;

use Filament\Forms;
use Filament\Tables;
use App\Models\Employee;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Travel\TravelOrder;
use Illuminate\Validation\Rule;
use App\Models\Hr\OvertimeOrder;
use Filament\Resources\Resource;
use App\Models\Hr\CocApplication;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Section;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Hr\CocApplicationResource\Pages;
use App\Filament\Resources\Hr\CocApplicationResource\RelationManagers\ApprovalStagesRelationManager;

class CocApplicationResource extends Resource
{
    protected static ?string $model = CocApplication::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Leave Management';

    protected static ?int $navigationSort = 6;



    public static function form(Form $form): Form
    {

        // Assuming you have a User model with a role and employee relationship
        $user = Auth::user();
        return $form
            ->schema([

                Section::make('Compensatory Overtime Credit Information')
                    ->schema([
                        Forms\Components\DatePicker::make('date_filed')
                            ->label('Date Filed')
                            ->required()
                            ->rule(function () {
                                $cocApplicationId = request()->route('record') ?? null; // Get record ID
                                return Rule::unique('coc_application_details', 'date_earned')
                                    ->where('coc_application_id', $cocApplicationId);
                            }),

                        Forms\Components\Hidden::make('employee_id')
                            ->default(fn() => Auth::user()->employee->id)
                            ->required(),



                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->required(),

                    ])->columns(2),

                Section::make('Compensatory Overtime Credit Details')
                    ->schema([
                        Forms\Components\Repeater::make('details')
                            ->relationship('details')
                            ->schema([
                                Forms\Components\DatePicker::make('date_earned')
                                    ->label('Date Earned')
                                    ->required()


                                    ->rule(Rule::unique('coc_application_details', 'date_earned')
                                        ->where(function ($query) {
                                            return $query->where('coc_application_id', request()->input('coc_application_id'));
                                        })),
                                Forms\Components\TextInput::make('hours_earned')
                                    ->label('Hours Earned')
                                    ->required()
                                    ->maxValue(8)
                                    ->minValue(4)
                                    ->numeric(),

                                Forms\Components\Select::make('travel_order_id')
                                    ->label('Travel Order')
                                    ->options(function () {
                                        $user = Auth::user();

                                        if (!$user->employee) {
                                            return [];
                                        }

                                        return TravelOrder::where('status', 'completed')
                                            ->whereHas('details', function ($query) use ($user) {
                                                $query->where('employee_id', $user->employee->id);
                                            })
                                            ->pluck('purpose', 'id');
                                    })
                                    ->nullable(),

                                Forms\Components\Select::make('overtime_order_id')
                                    ->label('Overtime Order')
                                    ->options(function () {
                                        $user = Auth::user();

                                        if (!$user->employee) {
                                            return [];
                                        }

                                        return OvertimeOrder::where('status', 'approved')
                                            ->whereHas('details', function ($query) use ($user) {
                                                $query->where('employee_id', $user->employee->id);
                                            })
                                            ->pluck('purpose', 'id');
                                    })
                                    ->nullable(),





                            ])
                            ->label('COC Details')
                            ->columns(4)
                            ->createItemButtonLabel('Add Detail'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date_filed')
                    ->label('Date Filed')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employee')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Applied At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])->modifyQueryUsing(function (Builder $query) {
                Log::info('UserResource modifyQueryUsing method called');

                $user = Auth::user();
                Log::info('UserResource modifyQueryUsing method called' . $user);

                //  Log::info('User Info', ['id' => $user->id, 'roles' => $user->getRoleNames()]);

                // Non-admin users should only see their own record
                if (!$user->hasAnyRole(['hr-admin', 'admin', 'leave-approver'])) {
                    Log::info('Non-admin user, modifying query', ['user_id' => $user->id]);
                    $query->where('employee_id', $user->employee->id);
                }


                // Log the non-admin user's info
                Log::info('Non-admin user, modifying query', ['user_id' => $user->id]);

                // Non-admin users should see their own records or records where they are an approver
                $query->where(function ($query) use ($user) {
                    $query->where('coc_applications.employee_id', $user->employee->id)
                        ->orWhereIn('coc_applications.id', function ($subQuery) use ($user) {
                            $subQuery->select('coc_application_id')
                                ->from('coc_approval_stages')
                                ->where('employee_id', $user->employee->id);
                        });
                });
                return $query;
            });
    }

    public static function getRelations(): array
    {


        $user = Auth::user(); // Get the current authenticated user

        // Check if the user has 'hr-admin' or 'admin' roles
        // if ($user && $user->hasAnyRole(['hr-admin', 'leave-admin', 'admin','user'])) {
        return [
            ApprovalStagesRelationManager::class, // Include only for specified roles
        ];
        //}

        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCocApplications::route('/'),
            'create' => Pages\CreateCocApplication::route('/create'),
            'edit' => Pages\EditCocApplication::route('/{record}/edit'),
            'view' => Pages\ViewCocApplication::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        // If the user has the 'admin' role, return all records
        if ($user->hasRole('admin')) {
            return parent::getEloquentQuery();
        }

        // For regular users, only show their own records
        if ($user->employee) {
            return parent::getEloquentQuery()->where('employee_id', $user->employee->id);
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
