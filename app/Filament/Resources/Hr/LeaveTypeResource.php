<?php

namespace App\Filament\Resources\Hr;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Hr\LeaveType;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Hr\LeaveTypeResource\Pages;

class LeaveTypeResource extends Resource
{
    protected static ?string $model = LeaveType::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Leave Management';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('leave_name')
                    ->required()
                    ->maxLength(255)
                    ->label('Leave Name'),
                Forms\Components\TextInput::make('accrual_rate')
                    ->numeric()
                    ->default(null)
                    ->label('Accrual Rate'),
                Forms\Components\TextInput::make('expiration_days')
                    ->numeric()
                    ->default(null)
                    ->label('Expiration Days'),
                Forms\Components\TextInput::make('fixed_expiry')
                    ->maxLength(5)
                    ->default(null)
                    ->label('Fixed Expiry (MM-DD)')
                    ->placeholder('e.g., 12-31'),
                Forms\Components\Select::make('frequency')
                    ->required()
                    ->options([
                        'monthly' => 'Monthly',
                        'yearly' => 'Yearly',
                        'event_based' => 'Event-Based',
                    ])
                    ->label('Frequency'),
                Forms\Components\Textarea::make('notes')
                    ->maxLength(255)
                    ->default(null)
                    ->label('Notes'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('leave_name')
                    ->searchable()
                    ->label('Leave Name'),
                Tables\Columns\TextColumn::make('accrual_rate')
                    ->numeric()
                    ->sortable()
                    ->label('Accrual Rate'),
                Tables\Columns\TextColumn::make('expiration_days')
                    ->numeric()
                    ->sortable()
                    ->label('Expiration Days'),
                Tables\Columns\TextColumn::make('fixed_expiry')
                    ->label('Fixed Expiry')
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        if ($state) {
                            $expiryDate = \Carbon\Carbon::createFromFormat('m-d', $state);
                            return $expiryDate->format('M d'); // Format as "Dec 31"
                        }
                        return '-'; // Display a dash if no expiry is set
                    }),
                Tables\Columns\TextColumn::make('frequency')
                    ->sortable()
                    ->label('Frequency'),
                Tables\Columns\TextColumn::make('notes')
                    ->searchable()
                    ->label('Notes'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Created At')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Updated At')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Add filters here if needed
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            // Define relations here if needed
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaveTypes::route('/'),
            'create' => Pages\CreateLeaveType::route('/create'),
            'edit' => Pages\EditLeaveType::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        // Show navigation if the user is an admin or has an associated employee
        return  $user?->employee !== null && $user?->hasAnyRole(['admin']) && $user->employee->employee_no != null;
    }
}
