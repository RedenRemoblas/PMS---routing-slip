<?php

namespace App\Filament\Resources\Hr;

use Filament\Forms;
use Filament\Tables;
use App\Models\Employee;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Hr\OvertimeOrder;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Hr\ReviewOvertimeOrderResource\Pages;

class ReviewOvertimeOrderResource extends Resource
{
    protected static ?string $model = OvertimeOrder::class;

    protected static ?string $navigationIcon = 'heroicon-s-arrow-top-right-on-square';

    protected static ?string $navigationGroup = 'Overtime Management';

    public static function getLabel(): string
    {
        return 'Review Overtime Order';
    }

    public static function getPluralLabel(): string
    {
        return 'Review Overtime Orders';
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        return parent::getEloquentQuery()
            ->whereHas('approvalStages', function ($query) use ($user) {
                $query->where('employee_id', $user->employee->id);
            })
            ->where('status', 'locked');
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Overtime Order Information')
                    ->schema([


                        Grid::make(2)->schema([
                            Forms\Components\TextInput::make('date_filed')
                                ->default(now())
                                ->disabled(),


                            Forms\Components\TextInput::make('status')
                                ->label('Status')
                                ->disabled()
                                ->default('Pending'),
                        ]),

                        Forms\Components\Textarea::make('purpose')
                            ->label('Purpose of Overtime')
                            ->helperText("Include in the purpose the details such as when, where and why.")
                            ->disabled()
                            ->columnSpanFull(),
                    ]),

                Section::make('Overtime Details')
                    ->schema([
                        Repeater::make('overtime_order_details')
                            ->relationship('details')
                            ->label('List of Employees')
                            ->schema([
                                Grid::make(4)->schema([
                                    Forms\Components\TextInput::make('employee_id')
                                        ->label('Employee Name')
                                        ->disabled()

                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('position')
                                        ->label('Position')
                                        ->disabled()
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('division')
                                        ->label('Division')
                                        ->disabled()
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('hours_rendered')
                                        ->numeric()
                                        ->minValue(4)
                                        ->disabled()
                                        ->label('Hours Rendered')
                                        ->columnSpan(1),
                                ]),
                            ])
                            ->deletable(false) // Remove delete button
                            ->addable(false) // Remove add employee button
                            //   ->createItemButtonLabel('Add Employee')
                            ->columns(1)
                            ->columnSpan('full'),
                    ])

                    ->collapsed(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('Overtime No.')->sortable(),
                Tables\Columns\TextColumn::make('date_filed')->date()->sortable(),
                Tables\Columns\TextColumn::make('creator.full_name')->label('Employee')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('purpose')
                    ->label('Purpose')
                    ->formatStateUsing(fn($state) => strlen($state) > 40 ? substr($state, 0, 40) . '...' : $state),

                Tables\Columns\TextColumn::make('status')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordUrl(null)
            ->actions([
                Action::make('review')
                    ->label('Review')
                    ->url(fn($record) => static::getUrl('edit', ['record' => $record->getKey()]))
                    ->color('primary')
                    ->hidden(fn($record) => in_array($record->status, ['Approved', 'Disapproved'])), // Hide if overtime is approved
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReviewOvertimeOrders::route('/'),
            'create' => Pages\CreateReviewOvertimeOrder::route('/create'),
            'edit' => Pages\EditReviewOvertimeOrder::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        // Ensure the user has an employee relationship and a valid employee number
        return $user?->employee !== null && $user->employee->employee_no !== null;
    }
}
