<?php

namespace App\Filament\Resources\Hr;

use Filament\Forms;
use Filament\Tables;
use App\Models\Employee;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\Hr\CocApplication;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Hr\ReviewCocApplicationResource\Pages;

class ReviewCocApplicationResource extends Resource
{
    protected static ?string $model = CocApplication::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard';



    protected static ?string $navigationGroup = 'Leave Management';

    protected static ?int $navigationSort = 8;

    public static function getLabel(): string
    {
        return 'Review COC Application';
    }

    public static function getPluralLabel(): string
    {
        return 'Review COC Applications';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('status', '!=', 'Pending');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('COC Application Information')
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

                        Forms\Components\Textarea::make('description')
                            ->label('Description of COC Application')
                            ->helperText("Provide details about the COC application.")
                            ->disabled()
                            ->columnSpanFull(),
                    ]),

                Section::make('COC Application Details')
                    ->schema([
                        Repeater::make('coc_application_details')
                            ->relationship('details')
                            ->label('Details of COC Application')
                            ->schema([
                                Grid::make(3)->schema([
                                    Forms\Components\TextInput::make('date_earned')
                                        ->label('Date Earned')
                                        ->disabled()
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('hours_earned')
                                        ->numeric()
                                        ->disabled()
                                        ->label('Hours Earned')
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('remarks')
                                        ->label('Remarks')
                                        ->disabled()
                                        ->columnSpan(1),
                                ]),
                            ])
                            ->deletable(false) // Remove delete button
                            ->addable(false) // Remove add item button
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
                Tables\Columns\TextColumn::make('id')->label('COC Application No.')->sortable(),
                Tables\Columns\TextColumn::make('date_filed')->date()->sortable(),
                Tables\Columns\TextColumn::make('employee.full_name')->label('Employee')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
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
                    ->hidden(fn($record) => in_array($record->status, ['Approved', 'Disapproved'])), // Hide if COC is approved
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReviewCocApplications::route('/'),
            'create' => Pages\CreateReviewCocApplication::route('/create'),
            'edit' => Pages\EditReviewCocApplication::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        // Ensure the user has an employee relationship and a valid employee number
        return $user?->employee !== null && $user->employee->employee_no !== null;
    }
}
