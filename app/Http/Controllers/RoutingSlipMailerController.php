<?php

namespace App\Http\Controllers;

use App\Mail\RoutingSlipCCViewOnlyNotification;
use App\Models\Document\RoutingSlip;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class RoutingSlipMailerController extends Controller
{
    /**
     * Send emails to CC recipients stored in the database
     * 
     * @param RoutingSlip $document The routing slip document
     * @param array|null $additionalCcRecipients Optional additional CC recipients not in the database
     * @param string|null $remarks Optional remarks to include in the email
     * @return void
     */
    public function sendCCEmails(RoutingSlip $document, ?array $additionalCcRecipients = [], ?string $remarks = null): void
    {
        // Prepare arrays to collect all CC recipients
        $allCcRecipients = [];
        $recipientNames = [];
        $recipientIsApprover = [];
        
        // Get all approver user IDs for this document
        $approverIds = $document->sequences()->pluck('user_id')->toArray();
        
        // First, get all CC recipients from the database
        $dbCcRecipients = $document->ccRecipients()->get();
        
        foreach ($dbCcRecipients as $dbRecipient) {
            if (filter_var($dbRecipient->email, FILTER_VALIDATE_EMAIL)) {
                $email = $dbRecipient->email;
                $userId = $dbRecipient->user_id ?? null;
                
                $allCcRecipients[] = $email;
                $recipientNames[$email] = $dbRecipient->name;
                // Check if this recipient is also an approver
                $recipientIsApprover[$email] = $userId && in_array($userId, $approverIds);
            }
        }
        
        // Then process any additional CC recipients passed to the method
        if (!empty($additionalCcRecipients)) {
            foreach ($additionalCcRecipients as $recipient) {
                // Handle different formats of CC recipients
                if (is_array($recipient)) {
                    // This is from the form with email, name, etc.
                    if (!empty($recipient['email']) && filter_var($recipient['email'], FILTER_VALIDATE_EMAIL)) {
                        $email = $recipient['email'];
                        $userId = $recipient['user_id'] ?? null;
                        
                        $allCcRecipients[] = $email;
                        $recipientNames[$email] = $recipient['name'] ?? null;
                        // Check if this recipient is also an approver
                        $recipientIsApprover[$email] = $userId && in_array($userId, $approverIds);
                    }
                } elseif (is_numeric($recipient)) {
                    // This is a user ID
                    $user = User::find($recipient);
                    if ($user && filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                        $email = $user->email;
                        
                        $allCcRecipients[] = $email;
                        $recipientNames[$email] = $user->name;
                        // Check if this recipient is also an approver
                        $recipientIsApprover[$email] = in_array($user->id, $approverIds);
                    }
                } elseif (is_string($recipient) && filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                    // This is just an email address
                    $email = $recipient;
                    
                    $allCcRecipients[] = $email;
                    $recipientNames[$email] = null;
                    // External email addresses are never approvers
                    $recipientIsApprover[$email] = false;
                }
            }
        }
        
        // If we have recipients, send individual emails to each recipient
        if (!empty($allCcRecipients)) {
            // Get the document creator's email for the From field
            $documentCreator = $document->creator ? $document->creator->email : config('mail.from.address');
            
            // Send individual emails to each recipient with the appropriate template
            foreach ($allCcRecipients as $recipientEmail) {
                $isApprover = $recipientIsApprover[$recipientEmail] ?? false;
                $name = $recipientNames[$recipientEmail] ?? null;
                
                // Send the view-only notification to all CC recipients, including approvers
                $notificationClass = new RoutingSlipCCViewOnlyNotification($document, $remarks, $name);
                
                Mail::to($recipientEmail)->send($notificationClass);
            }
        }
    }
    }
}
