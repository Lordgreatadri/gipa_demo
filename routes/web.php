<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DistrictWorkflowController;
use App\Http\Controllers\Admin\OpportunityWorkflowController;
use App\Http\Controllers\Admin\OpportunityReferenceDataController;
use App\Http\Controllers\PublicPortal\OpportunityController;
use App\Services\PublicOpportunityFilters;
use Illuminate\Support\Facades\Route;

Route::get('/', function (PublicOpportunityFilters $filters) {
    return view('welcome', ['filters' => $filters->options()]);
})->name('home');

Route::get('/opportunities', [OpportunityController::class, 'index'])->name('opportunities.index');
Route::get('/opportunities/{opportunity}', [OpportunityController::class, 'show'])->name('opportunities.show');
Route::post('/opportunities/{opportunity}/inquiries', [OpportunityController::class, 'storeInquiry'])
    ->middleware('throttle:5,1')
    ->name('opportunities.inquiries.store');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'createInvestor'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'storeInvestor'])->name('login.store');
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:6,1')->name('register.store');

    Route::get('/staff/login', [AuthenticatedSessionController::class, 'createStaff'])->name('staff.login');
    Route::post('/staff/login', [AuthenticatedSessionController::class, 'storeStaff'])->name('staff.login.store');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware('signed')->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])->middleware('throttle:6,1')->name('verification.send');

    Route::view('/portal', 'portal.investor')->middleware('verified')->name('investor.dashboard');
});

Route::prefix('staff')->name('staff.')->middleware(['auth', 'staff'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/reference-data', [OpportunityReferenceDataController::class, 'index'])->name('reference-data.index');
    Route::post('/reference-data/{type}', [OpportunityReferenceDataController::class, 'store'])->name('reference-data.store');
    Route::put('/reference-data/{type}/{record}', [OpportunityReferenceDataController::class, 'update'])->name('reference-data.update');
    Route::delete('/reference-data/{type}/{record}', [OpportunityReferenceDataController::class, 'destroy'])->name('reference-data.destroy');
    Route::get('/opportunities', [OpportunityWorkflowController::class, 'index'])->name('opportunities.index');
    Route::get('/opportunities/create', [OpportunityWorkflowController::class, 'create'])->name('opportunities.create');
    Route::post('/opportunities', [OpportunityWorkflowController::class, 'store'])->name('opportunities.store');
    Route::get('/opportunities/{opportunity}/edit', [OpportunityWorkflowController::class, 'edit'])->name('opportunities.edit');
    Route::put('/opportunities/{opportunity}', [OpportunityWorkflowController::class, 'update'])->name('opportunities.update');
    Route::delete('/opportunities/{opportunity}', [OpportunityWorkflowController::class, 'destroy'])->name('opportunities.destroy');
    Route::get('/opportunities/{opportunity}', [OpportunityWorkflowController::class, 'show'])->name('opportunities.show');
    Route::post('/opportunities/{opportunity}/{action}', [OpportunityWorkflowController::class, 'transition'])->name('opportunities.transition');
    Route::get('/districts', [DistrictWorkflowController::class, 'index'])->name('districts.index');
    Route::get('/districts/create', [DistrictWorkflowController::class, 'create'])->name('districts.create');
    Route::post('/districts', [DistrictWorkflowController::class, 'store'])->name('districts.store');
    Route::get('/districts/{district}/edit', [DistrictWorkflowController::class, 'edit'])->name('districts.edit');
    Route::put('/districts/{district}', [DistrictWorkflowController::class, 'update'])->name('districts.update');
    Route::delete('/districts/{district}', [DistrictWorkflowController::class, 'destroy'])->name('districts.destroy');
    Route::get('/districts/{district}', [DistrictWorkflowController::class, 'show'])->name('districts.show');
    Route::post('/districts/{district}/{action}', [DistrictWorkflowController::class, 'transition'])->name('districts.transition');
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'iomp-web',
        'timestamp' => now()->toIso8601String(),
    ]);
})->name('health');
