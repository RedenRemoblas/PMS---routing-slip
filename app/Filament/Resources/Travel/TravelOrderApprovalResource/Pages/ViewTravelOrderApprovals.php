<?php


namespace App\Filament\Resources\Travel\TravelOrderApprovalResource\Pages;

use Filament\Actions\Action;
use App\Models\Travel\TravelOrder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Filament\Pages\Actions\EditAction;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Filament\Pages\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\Travel\TravelOrderResource;
use App\Filament\Resources\Travel\TravelOrderApprovalResource;

class ViewTravelOrderApprovals extends ViewRecord
{
    // protected static string $resource = TravelOrderResource::class;
    protected static string $resource = TravelOrderApprovalResource::class;


    protected function getActions(): array
    {


        $user = Auth::user();
        $currentApprovalStage = $this->record->currentApprovalStageForUser($user->employee->id);
        $isCurrentApprover = $currentApprovalStage !== null;

        return [
            Action::make('approve')
                ->label('Approve')
                ->requiresConfirmation()
                ->action(function () {
                    $currentApprovalStage = $this->record->currentApprovalStage();

                    if ($currentApprovalStage) {
                        $status = $currentApprovalStage->approve();

                        if ($status === 'approved') {
                            Notification::make()
                                ->title('Travel Order Approved')
                                ->body('Travel order approved successfully.')
                                ->success()
                                ->send();
                        } elseif ($status === 'endorsed') {
                            Notification::make()
                                ->title('Stage Approved')
                                ->body('Stage approved. Waiting for next approver.')
                                ->success()
                                ->send();
                        }

                        $this->record->refresh();
                    }
                })
                ->hidden(fn() => !$isCurrentApprover),

            Action::make('reject')
                ->label('Reject')
                ->requiresConfirmation()
                ->action(function () use ($currentApprovalStage) {
                    if ($currentApprovalStage) {
                        $currentApprovalStage->update(['status' => 'rejected']);

                        $this->record->update(['status' => 'Rejected']);
                        $this->record->refresh();

                        Notification::make()
                            ->title('Travel Order Rejected')
                            ->body('Travel order rejected.')
                            ->success()
                            ->send();
                    }
                })
                ->hidden(fn() => !$isCurrentApprover),

            Action::make('generate_pdf')
                ->label('View PDF')
                ->url(fn() => route('travel-order.pdf', ['travelOrder' => $this->record->getKey()]))
                ->openUrlInNewTab()
                ->hidden(fn() => ($this->record->status !== 'Approved')),
        ];
    }

    protected function view(): string
    {
        return 'travel_order.blade.php';
    }
}
