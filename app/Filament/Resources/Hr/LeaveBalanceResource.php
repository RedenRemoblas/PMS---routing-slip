<?php

namespace App\Filament\Resources\Hr;

use App\Filament\Resources\Hr\LeaveBalanceResource\Pages;
use App\Models\Employee;
use App\Models\Hr\LeaveBalance;
use App\Models\Hr\LeaveType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LeaveBalanceResource extends Resource
{
    protected static ?string $model = LeaveBalance::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    protected static ?string $navigationGroup = 'Leave Management';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('employee_id')
                    ->label('Employee')
                    ->options(Employee::all()->pluck('full_name', 'id'))
                    ->searchable()
                    ->required(),

                Forms\Components\Select::make('leave_type_id')
                    ->label('Leave Type')
                    ->options(LeaveType::pluck('leave_name', 'id'))
                    ->searchable()
                    ->required(),

                Forms\Components\TextInput::make('days_remaining')
                    ->required()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee_id')
                    ->label('Id')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employee')
                    ->sortable()
                    ->searchable(['employees.firstname', 'employees.lastname', 'employees.middlename']),
                Tables\Columns\TextColumn::make('leaveType.leave_name')
                    ->label('Leave Type')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('days_remaining')
                    ->label('Days Remaining')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('days_reserved')
                    ->label('Days Reserved')
                    ->sortable()
                    ->searchable(),


                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('leave_type_id')
                    ->label('Leave Type')
                    ->options(LeaveType::pluck('leave_name', 'id')->toArray()), // Fetch options dynamically
                Tables\Filters\SelectFilter::make('employee_id')
                    ->label('Employee')
                    ->options(Employee::all()->pluck('full_name', 'id')->toArray()), // Fetch options dynamically
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                //      Tables\Actions\DeleteBulkAction::make(),
            ])->modifyQueryUsing(function (Builder $query) {

                $user = auth()->user();

                // Non-admin users should only see their own record
                if (! $user->hasAnyRole(['leave-admin', 'hr-admin'])) {
                    $query->where('employee_id', $user->employee->id);
                } else {
                    Log::info('Admin user, no query modification');
                }

                return $query;
            });
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
            'index' => Pages\ListLeaveBalances::route('/'),

            'edit' => Pages\EditLeaveBalance::route('/{record}/edit'),
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
