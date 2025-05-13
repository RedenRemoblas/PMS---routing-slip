<?php

namespace App\Http\Controllers\RoutingSlip;

use App\Http\Controllers\Controller;
use App\Models\Document\RoutingSlip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QrRedirectController extends Controller
{
    /**
     * Handle QR code redirection based on user role
     *
     * @param RoutingSlip $routingSlip
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectToAppropriateView(RoutingSlip $routingSlip)
    {
        // Ensure user is authenticated
        if (!Auth::check()) {
            return redirect()->route('filament.admin.auth.login');
        }

        // Get the current user ID
        $userId = Auth::id();

        // Check if the user is in the routing sequence and has pending approval
        $currentSequence = $routingSlip->sequences()
            ->where('status', 'pending')
            ->where('user_id', $userId)
            ->orderBy('sequence_number')
            ->first();

        // If user is in the sequence and has pending approval, redirect to review page
        if ($currentSequence) {
            return redirect()->to(\App\Filament\Resources\RoutingSlip\DocumentResource::getUrl('review', ['record' => $routingSlip]));
        }

        // Otherwise, redirect to view page
        return redirect()->to(\App\Filament\Resources\RoutingSlip\DocumentResource::getUrl('view', ['record' => $routingSlip]));
    }
}