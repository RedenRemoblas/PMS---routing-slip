<?php

namespace App\Filament\Resources;

use Filament\Tables;
use App\Models\Employee;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\DefaultApprover;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use App\Filament\Resources\DefaultApproverResource\Pages;

class DefaultApproverResource extends Resource
{
    protected static ?string $model = DefaultApprover::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';


    protected static ?string $navigationGroup = 'Settings';





    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2)->schema([
                    Select::make('employee_id')
                        ->label('Employee')
                        ->options(Employee::all()->mapWithKeys(function ($employee) {
                            return [$employee->id => $employee->full_name];
                        }))
                        ->required()
                        ->searchable(),

                    Select::make('division_id')
                        ->label('Division')
                        ->relationship('division', 'name')
                        ->required(),

                    TextInput::make('sequence')
                        ->helperText('NOTE: Ensure the sequence number is unique and correctly indicates the approver\'s order in the workflow.')
                        ->numeric()
                        ->required()
                        ->label('Approval Sequence'),

                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.full_name')
                    ->label('Employee')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('division.name')
                    ->label('Division')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('sequence')
                    ->label('Sequence')
                    ->sortable()
                    ->searchable(),

            ])
            ->filters([
                // Add any table filters here
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Define any relations here, if needed
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDefaultApprovers::route('/'),
            'create' => Pages\CreateDefaultApprover::route('/create'),
            'edit' => Pages\EditDefaultApprover::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        // Show navigation if the user is an admin or has an associated employee
        return $user?->hasAnyRole(['hr-admin', 'admin']);
    }
}
