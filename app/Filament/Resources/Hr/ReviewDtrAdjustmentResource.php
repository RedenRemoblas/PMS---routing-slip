<?php

namespace App\Filament\Resources\Hr;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Hr\DtrAdjustment;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use App\Models\Hr\DtrAdjustmentRequest;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Hr\ReviewDtrAdjustmentResource\Pages;

class ReviewDtrAdjustmentResource extends Resource
{
    protected static ?string $model = DtrAdjustmentRequest::class;

    protected static ?string $navigationIcon = 'heroicon-s-clock';

    protected static ?string $navigationGroup = 'DTR Management';

    public static function getLabel(): string
    {
        return 'Review DTR Adjustment';
    }

    public static function getPluralLabel(): string
    {
        return 'Review DTR Adjustments';
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
                Section::make('DTR Adjustment Information')
                    ->schema([
                        Grid::make(2)->schema([




                            TextInput::make('month_year')->label('Period')->disabled(),

                            TextInput::make('status')
                                ->label('Status')
                                ->disabled(),

                        ]),


                    ]),

                Section::make('Entries')
                    ->schema([
                        Repeater::make('entries')
                            ->relationship('entries')
                            ->label('Adjustment Entries')
                            ->schema([
                                Grid::make(4)->schema([
                                    Forms\Components\TextInput::make('adjustment_datetime')
                                        ->label('Adjustment Date/Time')
                                        ->disabled(),


                                    Forms\Components\TextInput::make('logType')
                                        ->label('Log Type')

                                        ->disabled(),

                                    Forms\Components\TextInput::make('reason')
                                        ->label('Reason')

                                        ->disabled(),

                                    Forms\Components\TextInput::make('remarks')
                                        ->label('Remarks')

                                        ->disabled(),

                                ]),
                            ])
                            ->deletable(false) // Remove delete button
                            ->addable(false) // Remove add entry button
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
                Tables\Columns\TextColumn::make('id')->label('Adjustment No.')->sortable(),
                Tables\Columns\TextColumn::make('employee.fullname')->label('Employee')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('month_year')->label('Period')->sortable()
                    ->formatStateUsing(fn($state) => \Carbon\Carbon::parse($state)->format('Y - F')),


                Tables\Columns\TextColumn::make('reason')
                    ->label('Reason')
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
                    ->hidden(fn($record) => in_array($record->status, ['Approved', 'Rejected'])),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReviewDtrAdjustments::route('/'),
            'create' => Pages\CreateReviewDtrAdjustment::route('/create'),
            'edit' => Pages\EditReviewDtrAdjustment::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        // Ensure the user has an employee relationship and a valid employee number
        return $user?->employee !== null && $user->employee->employee_no !== null;
    }
}
