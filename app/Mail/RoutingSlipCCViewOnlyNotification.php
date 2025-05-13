<?php

namespace App\Mail;

use App\Models\Document\RoutingSlip;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RoutingSlipCCViewOnlyNotification extends Mailable
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
     * 
     * This method builds the email notification for CC recipients who are view-only.
     * It handles both individual and batch notifications to multiple recipients.
     */
    public function build(): self
    {
        try {
            // Use a more appropriate subject for CC recipients who are not approvers
            return $this->subject('Routing Slip Document Notification - For Your Information')
                        ->view('emails.routing-slip-cc-view-only')
                        ->with([
                            'document' => $this->document,
                            'remarks' => $this->remarks,
                            'recipientName' => $this->recipientName ?? 'Recipient',
                            'isMultipleRecipients' => $this->recipientName === null, // Flag to indicate multiple recipients
                        ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error building CC view-only email: {$e->getMessage()}");
            // Return a basic email if there's an error with the template
            return $this->subject('Routing Slip Document Notification - For Your Information')
                        ->text("You have been CC'd on Routing Slip #{$this->document->id}. Please check the system for details.");
        }
    }
}