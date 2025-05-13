<?php

namespace App\Filament\Resources\Hr;

use Filament\Forms;
use Filament\Tables;
use App\Models\Hr\Leave;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Hr\ReviewLeave;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\Hr\ReviewLeaveResource\Pages;
use App\Filament\Resources\Hr\ReviewLeaveResource\RelationManagers;

class ReviewLeaveResource extends Resource
{
    protected static ?string $model = Leave::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Leave Management';
    protected static ?int $navigationSort = 5;



    public static function getLabel(): string
    {
        return 'Review Leave';
    }

    public static function getPluralLabel(): string
    {
        return 'Review Leaves';
    }
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('leave_status', '=', 'locked');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Photo Copy of the Actual Leave Form')
                ->description('Upload the fully signed copy of the application form. Only PDF files are allowed.')
                ->schema([
                    FileUpload::make('uploaded_file_path')
                        ->label('Scanned Leave Application Form')
                        ->acceptedFileTypes(['application/pdf'])    // Only allow PDFs
                        ->directory('leave-uploads')          // Folder within your storage
                        ->visibility('public')                     // If you want a public URL
                        ->preserveFilenames()                      // Keep the original uploaded filename
                        ->required()
                        ->downloadable(false)                      // Disable download button for approvers
                        ->disabled(fn($record) => $record && $record->leave_status === 'approved'),
                ]),
        ])->columns(2);
    }



    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('Leave No.')->sortable(),
                Tables\Columns\TextColumn::make('date_filed')->date()->sortable(),
                Tables\Columns\TextColumn::make('employee.full_name')->label('Employee')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('leaveType.leave_name')->label('Leave Type')->sortable()->searchable(),

                Tables\Columns\TextColumn::make('total_days')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('leave_status')->sortable(),
                Tables\Columns\TextColumn::make('uploaded_file_path')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),

                //
            ])
            ->filters([])

            ->recordUrl(
                null
            )
            ->actions([
                Action::make('review')
                    ->label('Review')
                    ->url(fn($record) => static::getUrl('edit', ['record' => $record->getKey()]))
                    ->color('primary')
                    ->hidden(fn($record) => $record->leave_status === 'approved'), // Hide if leave is approved
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
            'index' => Pages\ListReviewLeaves::route('/'),
            'create' => Pages\CreateReviewLeave::route('/create'),
            'edit' => Pages\EditReviewLeave::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        // Check if the user has the 'leave-admin' role
        return  $user?->employee !== null && $user && $user->hasRole('leave-admin');
    }
}
