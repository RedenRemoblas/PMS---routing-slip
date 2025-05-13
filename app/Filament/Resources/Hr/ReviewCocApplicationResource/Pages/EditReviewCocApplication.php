<?php

namespace App\Filament\Resources\Hr\ReviewCocApplicationResource\Pages;

use Filament\Actions;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\Hr\ReviewCocApplicationResource;

class EditReviewCocApplication extends EditRecord
{
    protected static string $resource = ReviewCocApplicationResource::class;

    public function getHeading(): string
    {
        return 'COC Application No: ' . $this->record->id . " [Status: " . $this->record->status . "]";
    }

    public function getSubHeading(): string
    {
        return "[ Created by: " . $this->record->employee->full_name . "]";
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label('Approve')
                ->action(fn() => $this->record->approveApplication(true))
                ->visible(fn() => $this->record->currentApprovalStageForUser(Auth::id()) !== null)
                ->requiresConfirmation()
                ->color('success'),

            Actions\Action::make('reject')
                ->label('Disapprove')
                ->action(fn() => $this->record->approveApplication(false))
                ->visible(fn() => $this->record->currentApprovalStageForUser(Auth::id()) !== null)
                ->requiresConfirmation()
                ->color('danger')
                ->icon('heroicon-o-x-circle'),

            Actions\Action::make('cancel')
                ->label('Cancel')
                ->action(fn() => $this->redirect($this->getResource()::getUrl('index')))
                ->color('primary')
                ->icon('heroicon-o-arrow-left'),

            Actions\Action::make('view_related_order')
                ->label('View Related Order')
                ->url(fn() => $this->getRelatedOrderUrl())
                ->color('primary')
                ->visible(fn() => $this->hasRelatedOrder())
                ->icon('heroicon-o-link'),
        ];
    }


    protected function getRelatedOrderUrl(): ?string
    {
        $detail = $this->record->details->first();

        if ($detail) {
            if ($detail->travelOrder) {
                return route('filament.admin.resources.travel.travel-orders.view', ['record' => $detail->travelOrder->id]);
            }
        }
        if ($detail->overtimeOrder) {
            return route('filament.admin.resources.hr.overtime-orders.view', ['record' => $detail->overtimeOrder->id]);
        }


        return null;
    }

    protected function hasRelatedOrder(): bool
    {
        $detail = $this->record->details->first();

        return $detail && ($detail->travelOrder || $detail->overtimeOrder);
    }
}
