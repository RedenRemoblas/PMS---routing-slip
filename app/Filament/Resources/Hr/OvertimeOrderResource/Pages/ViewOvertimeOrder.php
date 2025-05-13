<?php

namespace App\Filament\Resources\Hr\OvertimeOrderResource\Pages;

use Filament\Tables\Table;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Section;
use Filament\Pages\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Filament\Pages\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms\Components\Placeholder;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Resources\Hr\OvertimeOrderResource;

class ViewOvertimeOrder extends ViewRecord
{
    protected static string $resource = OvertimeOrderResource::class;

    public function getSubheading(): ?string
    {
        return __('Created by ') . $this->record->creator->full_name;
    }

    public function getTitle(): string|Htmlable
    {
        return __('Overtime Order No.') . $this->record->id;
    }

    protected function getActions(): array
    {
        $user = Auth::user();
        $currentApprovalStage = $this->record->currentApprovalStageForUser($user->employee->id);
        $isCurrentApprover = $currentApprovalStage !== null;

        return [
            EditAction::make()
                ->label('Edit')
                ->url(fn() => $this->getResource()::getUrl('edit', ['record' => $this->record->getKey()]))
                ->after(function () {
                    return redirect($this->getResource()::getUrl('view', ['record' => $this->record->getKey()]));
                })
                ->hidden(fn() => $this->record->status !== 'Pending' || ! Auth::user()->can('edit', $this->record)),

            DeleteAction::make()
                ->label('Delete')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->delete();

                    Notification::make()
                        ->title('Overtime Order Deleted')
                        ->body('The overtime order has been successfully deleted.')
                        ->success()
                        ->send();

                    return $this->redirect(OvertimeOrderResource::getUrl('index'));
                })
                ->hidden(fn() => $this->record->status !== 'Pending' || ! Auth::user()->can('edit', $this->record)),

            Action::make('lock')
                ->label('Lock')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    $result = $this->record->lockOvertimeOrder();


                    $this->record->refresh();




                    if ($result) {
                        Notification::make()
                            ->title('Overtime Order Locked')
                            ->body('Overtime order locked successfully.')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Lock Failed')
                            ->body('Failed to lock the Overtime order. Check if default approver exist')
                            ->danger()
                            ->send();
                    }
                })
                ->hidden(fn() => $this->record->status !== 'Pending' || ! Auth::user()->can('edit', $this->record)),

            Action::make('generate_pdf')
                ->label('View PDF')
                ->url(fn() => route('overtime-order.pdf', ['overtimeOrder' => $this->record->getKey()]))
                ->openUrlInNewTab()

                ->hidden(fn() => !in_array($this->record->status, ['Approved'])),

        ];
    }
}
