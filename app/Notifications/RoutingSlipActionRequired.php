<?php

namespace App\Notifications;

use App\Models\Document\RoutingSlip;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RoutingSlipActionRequired extends Notification
{
    use Queueable;

    public function __construct(
        protected RoutingSlip $routingSlip
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('PMS Routing Slip Action Required: Document Review')
            ->line('A document requires your review.')
            ->line('Document Title: ' . $this->routingSlip->title)
            ->action('Review Document', url('/admin/routing-slip/documents/' . $this->routingSlip->id))
            ->line('Please review and take appropriate action.');
    }
}
