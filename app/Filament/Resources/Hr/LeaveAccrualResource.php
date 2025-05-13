<?php

namespace App\Filament\Resources\Hr;

use Filament\Forms;
use Filament\Tables;
use App\Models\Employee;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Hr\LeaveAccrual;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Hr\LeaveAccrualResource\Pages;

class LeaveAccrualResource extends Resource
{
    protected static ?string $model = LeaveAccrual::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-plus';

    protected static ?string $navigationGroup = 'Leave Management';
    protected static ?int $navigationSort = 2;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('employee_id')
                    ->label('Employee')
                    ->options(function () {
                        return Employee::all()->pluck('full_name', 'id');
                    })
                    ->searchable()
                    ->required(),

                Forms\Components\Select::make('leave_type_id')
                    ->relationship('leaveType', 'leave_name')
                    ->searchable()
                    ->required()
                    ->label('Leave Type'),

                Forms\Components\DatePicker::make('accrual_date')
                    ->required()
                    ->label('Accrual Date'),

                Forms\Components\TextInput::make('days_accrued')
                    ->required()
                    ->numeric()
                    ->label('Days Accrued'),

                Forms\Components\DatePicker::make('expiry_date')
                    ->nullable()
                    ->label('Expiry Date'),


            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employee')
                    ->sortable(),
                Tables\Columns\TextColumn::make('leaveType.leave_name')
                    ->label('Leave Type')
                    ->sortable(),
                Tables\Columns\TextColumn::make('accrual_date')
                    ->date()
                    ->label('Accrual Date')
                    ->sortable(),
                Tables\Columns\TextColumn::make('days_accrued')
                    ->numeric()
                    ->label('Days Accrued')
                    ->sortable(),
                Tables\Columns\TextColumn::make('expiry_date')
                    ->date()
                    ->label('Expiry Date')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Created At')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->label('Updated At')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('employee')
                    ->form([
                        Forms\Components\Select::make('employee_id')
                            ->label('Employee')
                            ->options(\App\Models\Employee::all()->mapWithKeys(function ($employee) {
                                return [$employee->id => $employee->full_name];
                            }))
                            ->searchable()
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query->when(
                            $data['employee_id'],
                            fn(Builder $query, $value) => $query->where('employee_id', $value)
                        );
                    })
                    ->label('Filter by Employee'),

                Tables\Filters\Filter::make('leave_type')
                    ->form([
                        Forms\Components\Select::make('leave_type_id')
                            ->options(\App\Models\Hr\LeaveType::pluck('leave_name', 'id'))
                            ->label('Leave Type'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query->when(
                            $data['leave_type_id'],
                            fn(Builder $query, $value) => $query->where('leave_type_id', $value)
                        );
                    })
                    ->label('Filter by Leave Type'),

                Tables\Filters\Filter::make('expired')
                    ->query(fn(Builder $query) => $query->whereNotNull('expiry_date')->where('expiry_date', '<', now()))
                    ->label('Show Expired Only'),

                Tables\Filters\Filter::make('accrual_date')
                    ->form([
                        Forms\Components\DatePicker::make('start_date')->label('Start Date'),
                        Forms\Components\DatePicker::make('end_date')->label('End Date'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['start_date'], fn($query, $date) => $query->where('accrual_date', '>=', $date))
                            ->when($data['end_date'], fn($query, $date) => $query->where('accrual_date', '<=', $date));
                    })
                    ->label('Filter by Accrual Date Range'),
            ])
            ->actions([]);
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
            'index' => Pages\ListLeaveAccruals::route('/'),
            'create' => Pages\CreateLeaveAccrual::route('/create'),

        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        // If the user has the 'admin' role, return all records
        if ($user->hasRole('leave-admin')) {
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
