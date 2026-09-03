<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CatalogController;
use App\Http\Controllers\API\InvestorController;
use App\Http\Controllers\API\OpportunityController as ApiOpportunityController;
use App\Http\Controllers\PublicPortal\CertificateVerificationController;
use App\Http\Controllers\PublicPortal\OpportunityController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:api-login')->name('auth.login');
    Route::post('/auth/refresh', [AuthController::class, 'refresh'])->middleware('throttle:api-refresh')->name('auth.refresh');
    Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware(['jwt', 'throttle:api-protected'])->name('auth.logout');
    Route::middleware('throttle:api-public')->group(function () {
        Route::get('/regions', [CatalogController::class, 'regions'])->name('regions.index');
        Route::get('/districts', [CatalogController::class, 'districts'])->name('districts.index');
        Route::get('/sectors', [CatalogController::class, 'sectors'])->name('sectors.index');
        Route::get('/sub-sectors', [CatalogController::class, 'subSectors'])->name('sub-sectors.index');
        Route::get('/opportunities', [ApiOpportunityController::class, 'index'])->name('opportunities.index');
        Route::get('/opportunities/{opportunity}', [ApiOpportunityController::class, 'show'])->name('opportunities.show');
    });
    Route::middleware(['jwt', 'throttle:api-protected'])->prefix('investor')->name('investor.')->group(function () {
        Route::get('/me', [InvestorController::class, 'show'])->name('show');
        Route::put('/preferences', [InvestorController::class, 'updatePreferences'])->name('preferences.update');
        Route::get('/matches', [InvestorController::class, 'matches'])->name('matches.index');
    });
    Route::get('/certificates/{token}/verify', [CertificateVerificationController::class, 'api'])
        ->middleware('throttle:api-certificate-verification')
        ->name('certificates.verify');
    Route::post('/opportunities/{opportunity}/inquiries', [OpportunityController::class, 'storeInquiry'])
        ->middleware('throttle:api-inquiry')
        ->name('opportunities.inquiries.store');
});
