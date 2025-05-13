<?php


namespace App\Filament\Resources\Hr;

use Filament\Tables;
use App\Models\Hr\Dtr;

use Filament\Forms\Form;
use Filament\Tables\Table;
use Pages\MonthlyDtrReport;
use Illuminate\Support\Carbon;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use App\Filament\Resources\Hr\DtrResource\Pages;
use Illuminate\Contracts\Database\Eloquent\Builder;


class DtrResource extends Resource
{
    protected static ?string $model = Dtr::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'DTR Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('employee_dtr_no')
                    ->label('Employee DTR No')
                    ->required()
                    ->maxLength(32),

                TextInput::make('device_serial_no')
                    ->label('Device Serial No')
                    ->required()
                    ->maxLength(50),

                TextInput::make('verify_mode')
                    ->label('Verify Mode')
                    ->required()
                    ->maxLength(8),

                DateTimePicker::make('dtr_timestamp')
                    ->label('DTR Timestamp')
                    ->required(),

                Select::make('log_type')
                    ->label('Log Type')
                    ->options([
                        'IN' => 'IN',
                        'OUT' => 'OUT',
                    ])
                    ->required(),

                TextInput::make('sequence_no')
                    ->label('Sequence No')
                    ->numeric()
                    ->required(),

                TextInput::make('remarks')
                    ->label('Remarks')
                    ->maxLength(65535)
                    ->nullable(),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([


                TextColumn::make('employee.fullname')->label('Name')->sortable()->searchable(),

                TextColumn::make('dtr_timestamp')
                    ->label('Date & Time')
                    ->sortable()

                    ->dateTime('F j, Y g:i A'),

                TextColumn::make('log_type')
                    ->label('Log Type')
                    ->sortable(),

                TextColumn::make('sequence_no')
                    ->label('Sequence No')
                    ->sortable(),

                TextColumn::make('remarks')
                    ->label('Remarks')
                    ->sortable()
                    ->searchable(),
            ])
            ->defaultSort('dtr_timestamp', 'asc')
            ->filters([
                Tables\Filters\Filter::make('month')
                    ->label('Select Month')
                    ->form([
                        DatePicker::make('month')
                            ->native(false)
                            ->format('Y-m')
                            ->label('Month')
                            ->displayFormat('F Y')
                            ->default(now())
                            //  ->extraAttributes([ 'onkeydown' => 'return false;', // Prevent keyboard input]) // Prevent manual input
                            ->required(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->whereMonth('dtr_timestamp', Carbon::parse($data['month'])->month)
                            ->whereYear('dtr_timestamp', Carbon::parse($data['month'])->year)
                            ->where('employee_dtr_no', auth()->user()->employee->employee_no);
                    })
                    ->indicateUsing(function (array $data): string {
                        return 'Month: ' . Carbon::parse($data['month'])->format('F Y');
                    }),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                //      Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListDtrs::route('/'),
            // 'create' => Pages\CreateDtr::route('/create'),
            //   'edit' => Pages\EditDtr::route('/{record}/edit'),
            //    'monthly-dtr-report' => Pages\MonthlyDtrReport::route('/monthly-dtr-report'), // Add this line

        ];
    }
    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        // Ensure the user has an employee relationship and a valid employee number
        return $user?->employee !== null && $user->employee->employee_no !== null;
    }
}
