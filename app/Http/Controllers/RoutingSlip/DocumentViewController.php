<?php

namespace App\Http\Controllers\RoutingSlip;

use App\Http\Controllers\Controller;
use App\Models\Document\RoutingSlip;
use Illuminate\Support\Facades\Auth;

class DocumentViewController extends Controller
{
    /**
     * View a routing slip document
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function viewDocument($id)
    {
        $routingSlip = RoutingSlip::findOrFail($id);
        
        // Check if user has permission to view this document
        $user = Auth::user();
        $canView = $routingSlip->creator_id === $user->id || 
                  $routingSlip->sequences()->where('user_id', $user->id)->exists() ||
                  $user->hasRole('super_admin');
                  
        if (!$canView) {
            abort(403, 'You do not have permission to view this document');
        }
        
        // Redirect to the Filament resource view page
        return redirect()->to(\App\Filament\Resources\RoutingSlip\DocumentResource::getUrl('view', ['record' => $routingSlip]));
    }
}