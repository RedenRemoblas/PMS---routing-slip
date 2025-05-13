<?php

namespace App\Http\Controllers\RoutingSlip;

use App\Http\Controllers\Controller;
use App\Models\Document\RoutingSlip;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;

class DocumentUpdateController extends Controller
{
    /**
     * Update document information
     */
    public function update(Request $request, $id)
    {
        $routingSlip = RoutingSlip::findOrFail($id);
        
        // Check if document is finalized
        if ($routingSlip->status === 'finalized') {
            Notification::make()
                ->title('Error')
                ->body('This document is finalized and cannot be edited.')
                ->danger()
                ->send();
                
            return redirect()->back();
        }
        
        // Update document information
        $routingSlip->update([
            'title' => $request->title,
            'remarks' => $request->remarks,
        ]);
        
        Notification::make()
            ->title('Success')
            ->body('Document information has been updated.')
            ->success()
            ->send();
            
        return redirect()->back();
    }
}