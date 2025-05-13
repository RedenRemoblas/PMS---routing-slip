<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\LeavePdfController;
use App\Http\Controllers\OvertimePdfController;
use App\Http\Controllers\RoutingSlipPdfController;
use App\Http\Controllers\TravelOrderPdfController;
use App\Http\Controllers\RoutingSlip\QrRedirectController;
use App\Filament\Resources\Hr\DtrResource\Pages\ViewDtr;
use App\Filament\Resources\Hr\DtrResource\Pages\MonthlyDtrReport;



// Landing page: Redirect based on authentication
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('filament.admin.pages.dashboard');
    }
    return redirect()->route('filament.admin.auth.login');
});

// Routes for Google login (public)
Route::get('social/google', [GoogleController::class, 'redirectToGoogle'])->name('google.redirect');
Route::get('social/google/callback', [GoogleController::class, 'handleGoogleCallback']);


// Protected routes
Route::middleware(['auth'])->group(function () {
    Route::get('/leave/{leave}/pdf', [LeavePdfController::class, 'generatePdf'])->name('leave.pdf');
    Route::get('/travel-order/{travelOrder}/pdf', [TravelOrderPdfController::class, 'generatePdf'])->name('travel-order.pdf');
    Route::get('/travel-order/{travelOrder}/download/{hash}', [TravelOrderPdfController::class, 'downloadPdf'])->name('travel.order.download');
    Route::get('/routing-slip/{routingSlip}/pdf', [RoutingSlipPdfController::class, 'generatePdf'])->name('routing-slip.pdf');
    Route::get('/routing-slip/{routingSlip}/download/{hash}', [RoutingSlipPdfController::class, 'downloadPdf'])->name('routing.slip.download');
    Route::get('/routing-slip/{routingSlip}/qr', [QrRedirectController::class, 'redirectToAppropriateView'])->name('routing-slip.qr');
    Route::get('/routing-slip/{id}/view-document', [\App\Http\Controllers\RoutingSlip\DocumentViewController::class, 'viewDocument'])->name('routing-slip.view-document');
    Route::post('/routing-slip/{id}/upload-file', [\App\Http\Controllers\RoutingSlip\FileUploadController::class, 'upload'])->name('routing-slip.upload-file');
    Route::put('/routing-slip/{id}/update-document', [\App\Http\Controllers\RoutingSlip\DocumentUpdateController::class, 'update'])->name('routing-slip.update-document');
    Route::get('/dtr/{month}/pdf', [MonthlyDtrReport::class, 'generateDtrPdf'])->name('dtr.view.pdf');
    Route::get('/dtr/view-dtr', [ViewDtr::class, 'render'])->name('dtr.view.dtr');
    Route::get('/overtime-order/{overtimeOrder}/pdf', [OvertimePdfController::class, 'generatePdf'])
        ->name('overtime-order.pdf');
    Route::get('/file/download/{id}', [\App\Http\Controllers\FileDownloadController::class, 'download'])->name('file.download');
    // Route for document.pdf removed
});
