<?php

namespace App\Mail;

use App\Models\Document\RoutingSlip;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RoutingSlipCCNotification extends Mailable
{
    use Queueable, SerializesModels;

    public RoutingSlip $document;
    public ?string $remarks;
    public ?string $recipientName;

    /**
     * Create a new message instance.
     */
    public function __construct(RoutingSlip $document, ?string $remarks = null, ?string $recipientName = null)
    {
        $this->document = $document;
        $this->remarks = $remarks;
        $this->recipientName = $recipientName;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        try {
            // Use a more appropriate subject for multiple CC recipients
            return $this->subject('Routing Slip Document CC Notification')
                        ->view('emails.routing-slip-cc')
                        ->with([
                            'document' => $this->document,
                            'remarks' => $this->remarks,
                            'recipientName' => $this->recipientName ?? 'Recipient',
                            'isMultipleRecipients' => $this->recipientName === null, // Flag to indicate multiple recipients
                        ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error building CC email: {$e->getMessage()}");
            // Return a basic email if there's an error with the template
            return $this->subject('Routing Slip Document CC Notification')
                        ->text("You have been CC'd on Routing Slip #{$this->document->id}. Please check the system for details.");
        }
    }
}