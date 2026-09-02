<?php

use App\Http\Controllers\PublicPortal\OpportunityController;
use App\Http\Controllers\PublicPortal\CertificateVerificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('/certificates/{token}/verify', [CertificateVerificationController::class, 'api'])
        ->middleware('throttle:30,1')
        ->name('certificates.verify');
    Route::get('/opportunities', [OpportunityController::class, 'index'])->name('opportunities.index');
    Route::get('/opportunities/{opportunity}', [OpportunityController::class, 'show'])->name('opportunities.show');
    Route::post('/opportunities/{opportunity}/inquiries', [OpportunityController::class, 'storeInquiry'])
        ->middleware('throttle:5,1')
        ->name('opportunities.inquiries.store');
});
