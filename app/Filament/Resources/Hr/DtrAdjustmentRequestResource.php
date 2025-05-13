<?php

namespace App\Filament\Resources\Hr;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use App\Models\Hr\DtrAdjustmentRequest;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\{TextColumn, BadgeColumn};
use App\Filament\Resources\Hr\DtrAdjustmentRequestResource\Pages;
use Filament\Forms\Components\{Section, DatePicker, TextInput, Textarea, Repeater, Select};
use App\Filament\Resources\Hr\DtrAdjustmentRequestResource\RelationManagers\ApprovalStagesRelationManager;

class DtrAdjustmentRequestResource extends Resource
{
    protected static ?string $model = DtrAdjustmentRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';



    protected static ?string $navigationGroup = 'DTR Management';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('DTR Adjustment Request Details')
                    ->schema([
                        DatePicker::make('month_year')
                            ->label('Month & Year')
                            ->format('Y-m') // Format for Year-Month
                            ->helperText('Choose any day of the month')


                            ->closeOnDateSelection() // Ensures the picker closes after selection
                            ->required(),



                        TextInput::make('status')
                            ->label('Status')
                            ->disabled(),

                        Hidden::make('created_by')
                            ->default(fn() => Auth::user()->employee->id) // Set default to the current employee's ID
                            ->required(),

                    ])
                    ->columns(2),

                Section::make('Adjustment Entries')
                    ->description('NOTE: Check the adjustment entries fall within the same month-year.')
                    ->schema([
                        Repeater::make('entries')
                            ->relationship('entries')
                            ->schema([
                                DateTimePicker::make('adjustment_datetime')
                                    ->label('Adjustment Date & Time')
                                    ->helperText('This will reflect in your DTR')
                                    ->required(),



                                Select::make('logType')
                                    ->label('Log Type')
                                    ->options([
                                        'IN' => 'IN',
                                        'BREAK OUT' => 'BREAK OUT',

                                        'BREAK IN' => 'BREAK IN',
                                        'OUT' => 'OUT',

                                    ])
                                    ->helperText('(IN-> AM In, BREAK-OUT-> Lunch Out)')
                                    ->required(),




                                TextInput::make('reason')
                                    ->label('Reason for Adjustment')
                                    ->required(),

                                TextInput::make('remarks')
                                    ->label('Remarks')
                                    ->nullable(),
                            ])
                            ->label('DTR Adjustment Entries')
                            ->columns(4)
                            ->createItemButtonLabel('Add Adjustment Entry'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('month_year')
                    ->label('Month & Year')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('employee.full_name')
                    ->label('Employee Name')
                    ->sortable()
                    ->searchable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'primary' => 'Pending',
                        'success' => 'Approved',
                        'danger' => 'Rejected',
                        'warning' => 'Cancelled',
                    ])
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Submitted At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                // You can add custom filters for status, date, etc.
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                //    Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                //   Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListDtrAdjustmentRequests::route('/'),
            'create' => Pages\CreateDtrAdjustmentRequest::route('/create'),
            'edit' => Pages\EditDtrAdjustmentRequest::route('/{record}/edit'),
            'view' => Pages\ViewDtrAdjustmentRequest::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        // Admins can see all records
        if ($user->hasRole('dtr-admin')) {
            return parent::getEloquentQuery();
        }

        // Non-admin users see their own requests
        if ($user->employee) {
            return parent::getEloquentQuery()
                ->where('created_by', $user->employee->id);
        }

        // Return an empty query if user is not associated with an employee
        return parent::getEloquentQuery()->whereRaw('1 = 0');
    }
    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        // Ensure the user has an employee relationship and a valid employee number
        return $user?->employee !== null && $user->employee->employee_no !== null;
    }
}
