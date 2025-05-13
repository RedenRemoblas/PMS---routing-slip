<?php

namespace App\Filament\Resources\Travel\TravelOrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ApprovalStagesRelationManager extends RelationManager
{
    protected static string $relationship = 'approvalStages';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('employee_id')
                    ->relationship('employee', 'lastname')
                    ->required()
                    ->label('Approver'),

                Forms\Components\TextInput::make('sequence')
                    ->numeric()
                    ->required()
                    ->label('Sequence'),

                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->required()
                    ->label('Status'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('travel_order_id')
                    ->label('Travel Order Id')
                    ->sortable(),

                TextColumn::make('employee.full_name')
                    ->label('Approver')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('sequence')
                    ->label('Sequence')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->sortable(),
                TextColumn::make('remarks')
                    ->label('Remarks')
                    ->sortable(),
            ])
            ->filters([
                // Add any filters you need here
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
