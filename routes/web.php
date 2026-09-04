<?php

use App\Http\Controllers\Admin\AssistantKnowledgeController;
use App\Http\Controllers\Admin\CertificateController as AdminCertificateController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DistrictWorkflowController;
use App\Http\Controllers\Admin\InvestorOnboardingController as AdminInvestorOnboardingController;
use App\Http\Controllers\Admin\OpportunityReferenceDataController;
use App\Http\Controllers\Admin\OpportunityWorkflowController;
use App\Http\Controllers\Admin\StaffDistrictAssignmentController;
use App\Http\Controllers\Admin\WorkspaceDirectoryController;
use App\Http\Controllers\AssistantController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\InvestorPortalController;
use App\Http\Controllers\PublicPortal\CertificateVerificationController;
use App\Http\Controllers\PublicPortal\DistrictController;
use App\Http\Controllers\PublicPortal\OpportunityController;
use App\Services\PublicOpportunityFilters;
use Illuminate\Support\Facades\Route;

Route::get('/', function (PublicOpportunityFilters $filters) {
    return view('welcome', ['filters' => $filters->options()]);
})->name('home');

Route::view('/api/documentation', 'api-documentation')->name('api.documentation');
Route::view('/guide', 'platform-guide')->name('platform.guide');

Route::get('/opportunities', [OpportunityController::class, 'index'])->name('opportunities.index');
Route::get('/opportunities/{opportunity}', [OpportunityController::class, 'show'])->name('opportunities.show');
Route::get('/districts', [DistrictController::class, 'index'])->name('districts.index');
Route::get('/districts/{district}', [DistrictController::class, 'show'])->name('districts.show');
Route::post('/opportunities/{opportunity}/inquiries', [OpportunityController::class, 'storeInquiry'])
    ->middleware('throttle:5,1')
    ->name('opportunities.inquiries.store');
Route::get('/c/{token}', [CertificateVerificationController::class, 'show'])
    ->middleware('throttle:30,1')
    ->name('certificates.verify');

Route::post('/assistant/chat', [AssistantController::class, 'store'])
    ->middleware('throttle:assistant')
    ->name('assistant.chat');

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

    Route::middleware('verified')->group(function () {
        Route::get('/portal', [InvestorPortalController::class, 'show'])->name('investor.dashboard');
        Route::patch('/portal/profile', [InvestorPortalController::class, 'updateProfile'])->name('investor.profile.update');
        Route::put('/portal/match-preferences', [InvestorPortalController::class, 'updateMatchPreferences'])->middleware('throttle:20,1')->name('investor.match-preferences.update');
        Route::post('/portal/onboarding', [InvestorPortalController::class, 'start'])->name('investor.onboarding.start');
        Route::post('/portal/onboarding/{case}/documents', [InvestorPortalController::class, 'upload'])->middleware('throttle:10,1')->name('investor.onboarding.documents.store');
        Route::post('/portal/onboarding/{case}/submit', [InvestorPortalController::class, 'submit'])->middleware('throttle:5,1')->name('investor.onboarding.submit');
        Route::get('/portal/documents/{document}', [InvestorPortalController::class, 'download'])->middleware('throttle:30,1')->name('investor.documents.download');
    });
});

Route::prefix('staff')->name('staff.')->middleware(['auth', 'staff'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::view('/guide', 'staff-guide')->name('guide');
    Route::get('/assistant/knowledge', [AssistantKnowledgeController::class, 'index'])->name('assistant.knowledge.index');
    Route::get('/assistant/knowledge/create', [AssistantKnowledgeController::class, 'create'])->name('assistant.knowledge.create');
    Route::post('/assistant/knowledge', [AssistantKnowledgeController::class, 'store'])->name('assistant.knowledge.store');
    Route::post('/assistant/knowledge/reindex', [AssistantKnowledgeController::class, 'reindexAll'])->name('assistant.knowledge.reindex-all');
    Route::get('/assistant/knowledge/{document}/edit', [AssistantKnowledgeController::class, 'edit'])->name('assistant.knowledge.edit');
    Route::put('/assistant/knowledge/{document}', [AssistantKnowledgeController::class, 'update'])->name('assistant.knowledge.update');
    Route::delete('/assistant/knowledge/{document}', [AssistantKnowledgeController::class, 'destroy'])->name('assistant.knowledge.destroy');
    Route::post('/assistant/knowledge/{document}/reindex', [AssistantKnowledgeController::class, 'reindex'])->name('assistant.knowledge.reindex');    Route::get('/opportunity-workspace', [WorkspaceDirectoryController::class, 'opportunities'])->name('opportunity-workspace');
    Route::get('/regions', [WorkspaceDirectoryController::class, 'regions'])->name('regions.index');
    Route::get('/investment-workspace', [WorkspaceDirectoryController::class, 'investments'])->name('investments.overview');
    Route::get('/inquiries', [WorkspaceDirectoryController::class, 'inquiries'])->name('inquiries.index');
    Route::get('/notifications', [WorkspaceDirectoryController::class, 'notificationsOverview'])->name('notifications.overview');
    Route::get('/notifications/list', [WorkspaceDirectoryController::class, 'notificationsIndex'])->name('notifications.index');
    Route::get('/users', [WorkspaceDirectoryController::class, 'usersOverview'])->name('users.overview');
    Route::get('/users/staff', [WorkspaceDirectoryController::class, 'usersStaff'])->name('users.staff');
    Route::get('/users/roles', [WorkspaceDirectoryController::class, 'usersRoles'])->name('users.roles');
    Route::get('/users/permissions', [WorkspaceDirectoryController::class, 'usersPermissions'])->name('users.permissions');
    Route::get('/reference-data', [OpportunityReferenceDataController::class, 'index'])->name('reference-data.index');
    Route::get('/reference-data/{section}', [OpportunityReferenceDataController::class, 'index'])->name('reference-data.section');
    Route::get('/certificate-assignments', [StaffDistrictAssignmentController::class, 'index'])->name('certificate-assignments.index');
    Route::post('/certificate-assignments', [StaffDistrictAssignmentController::class, 'store'])->name('certificate-assignments.store');
    Route::patch('/certificate-assignments/{assignment}/end', [StaffDistrictAssignmentController::class, 'end'])->name('certificate-assignments.end');
    Route::get('/certificates/overview', [AdminCertificateController::class, 'overview'])->name('certificates.overview');
    Route::get('/certificates', [AdminCertificateController::class, 'index'])->name('certificates.index');
    Route::get('/certificates/create', [AdminCertificateController::class, 'create'])->name('certificates.create');
    Route::post('/certificates', [AdminCertificateController::class, 'store'])->name('certificates.store');
    Route::get('/certificates/evidence/{verification}', [AdminCertificateController::class, 'evidence'])->middleware('throttle:30,1')->name('certificates.evidence');
    Route::get('/certificates/{certificate}/artifacts/{artifact}', [AdminCertificateController::class, 'artifact'])->middleware('throttle:30,1')->name('certificates.artifact');
    Route::get('/certificates/{certificate}', [AdminCertificateController::class, 'show'])->name('certificates.show');
    Route::post('/certificates/{certificate}/verify', [AdminCertificateController::class, 'verify'])->middleware('throttle:20,1')->name('certificates.verify');
    Route::post('/certificates/{certificate}/{action}', [AdminCertificateController::class, 'action'])->name('certificates.action');
    Route::get('/investors', [AdminInvestorOnboardingController::class, 'index'])->name('investors.index');
    Route::get('/investors-overview', [AdminInvestorOnboardingController::class, 'overview'])->name('investors.overview');
    Route::get('/investors/{case}', [AdminInvestorOnboardingController::class, 'show'])->name('investors.show');
    Route::post('/investors/{case}/{action}', [AdminInvestorOnboardingController::class, 'transition'])->name('investors.transition');
    Route::post('/investor-documents/{document}/{action}', [AdminInvestorOnboardingController::class, 'documentDecision'])->name('investor-documents.decision');
    Route::post('/reference-data/{type}', [OpportunityReferenceDataController::class, 'store'])->name('reference-data.store');
    Route::put('/reference-data/{type}/{record}', [OpportunityReferenceDataController::class, 'update'])->name('reference-data.update');
    Route::delete('/reference-data/{type}/{record}', [OpportunityReferenceDataController::class, 'destroy'])->name('reference-data.destroy');
    Route::get('/opportunities', [OpportunityWorkflowController::class, 'index'])->name('opportunities.index');
    Route::get('/opportunities-overview', [OpportunityWorkflowController::class, 'overview'])->name('opportunities.overview');
    Route::get('/opportunities/create', [OpportunityWorkflowController::class, 'create'])->name('opportunities.create');
    Route::post('/opportunities', [OpportunityWorkflowController::class, 'store'])->name('opportunities.store');
    Route::get('/opportunities/{opportunity}/edit', [OpportunityWorkflowController::class, 'edit'])->name('opportunities.edit');
    Route::put('/opportunities/{opportunity}', [OpportunityWorkflowController::class, 'update'])->name('opportunities.update');
    Route::delete('/opportunities/{opportunity}', [OpportunityWorkflowController::class, 'destroy'])->name('opportunities.destroy');
    Route::get('/opportunities/{opportunity}', [OpportunityWorkflowController::class, 'show'])->name('opportunities.show');
    Route::post('/opportunities/{opportunity}/{action}', [OpportunityWorkflowController::class, 'transition'])->name('opportunities.transition');
    Route::get('/districts', [DistrictWorkflowController::class, 'index'])->name('districts.index');
    Route::get('/districts-overview', [DistrictWorkflowController::class, 'overview'])->name('districts.overview');
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
