<?php

use App\Http\Controllers\Admin\CandidateController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DomainController;
use App\Http\Controllers\Admin\ThemeController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\TestTemplateController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TestController;
use Illuminate\Support\Facades\Route;

// Redirect root to admin dashboard or login
Route::get('/', fn() => redirect()->route('admin.dashboard'));

// Candidate test routes (public, token-based)
Route::prefix('test')->name('test.')->group(function () {
    Route::get('/{token}', [TestController::class, 'start'])->name('start');
    Route::post('/{token}/start', [TestController::class, 'begin'])->name('begin');
    Route::post('/{token}/answer', [TestController::class, 'saveAnswer'])->name('answer');
    Route::post('/{token}/execute', [TestController::class, 'executeCode'])->name('execute');
    Route::post('/{token}/submit', [TestController::class, 'submit'])->name('submit');
    Route::post('/{token}/activity', [TestController::class, 'logActivity'])->name('activity');
    Route::get('/{token}/result', [TestController::class, 'result'])->name('result');
});

// Admin routes (authenticated)
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::post('/sync-pull-data/{candidate}', [CandidateController::class, 'syncSpecificFromHubSpot'])->name('candidates.pull-data');
    Route::get('/pending', fn() => inertia('Admin/Pending'))->name('pending');
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Administrators
    Route::post('/administrators/{user}/validate', [\App\Http\Controllers\Admin\AdministratorController::class, 'validateUser'])->name('administrators.validate');
    Route::resource('administrators', \App\Http\Controllers\Admin\AdministratorController::class)->except(['show', 'create', 'edit']);
    Route::resource('roles', \App\Http\Controllers\Admin\RoleController::class)->except(['show', 'create', 'edit']);

    // Documentation
    Route::get('/documentation', fn() => inertia('Admin/Documentation'))->name('documentation');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Domains & Themes
    Route::get('/domains', [DomainController::class, 'index'])->name('domains.index');
    Route::post('/domains', [DomainController::class, 'store'])->name('domains.store');
    Route::put('/domains/{domain}', [DomainController::class, 'update'])->name('domains.update');
    Route::delete('/domains/{domain}', [DomainController::class, 'destroy'])->name('domains.destroy');
    Route::post('/domains/{domain}/themes', [DomainController::class, 'storeTheme'])->name('domains.themes.store');
    Route::delete('/domains/{domain}/themes/{theme}', [DomainController::class, 'detachTheme'])->name('domains.themes.detach');

    // Dedicated Themes Management
    Route::get('/themes', [ThemeController::class, 'index'])->name('themes.index');
    Route::post('/themes', [ThemeController::class, 'store'])->name('themes.store');
    Route::put('/themes/{theme}', [ThemeController::class, 'update'])->name('themes.update');
    Route::delete('/themes/{theme}', [ThemeController::class, 'destroy'])->name('themes.destroy');

    // Academic Levels
    Route::resource('levels', \App\Http\Controllers\Admin\AcademicLevelController::class)->except(['show']);

    // Questions
    Route::get('questions/template', [QuestionController::class, 'downloadTemplate'])->name('questions.template');
    Route::get('questions/export', [QuestionController::class, 'export'])->name('questions.export');
    Route::post('questions/import', [QuestionController::class, 'import'])->name('questions.import');
    Route::post('questions/test', [QuestionController::class, 'test'])->name('questions.test');
    Route::resource('questions', QuestionController::class)->except(['show']);

    // Templates
    Route::resource('templates', TestTemplateController::class)->except(['show']);

    // Candidates
    Route::get('/candidates', [CandidateController::class, 'index'])->name('candidates.index');
    Route::post('/candidates/refresh-all', [CandidateController::class, 'syncFromHubSpot'])->name('candidates.sync-hubspot');
    Route::post('/candidates/import', [CandidateController::class, 'import'])->name('candidates.import');
    Route::post('/candidates', [CandidateController::class, 'store'])->name('candidates.store');
    Route::get('/candidates/{candidate}', [CandidateController::class, 'show'])->name('candidates.show');
    Route::delete('/candidates/{candidate}', [CandidateController::class, 'destroy'])->name('candidates.destroy');
    Route::post('/candidates/{candidate}/push-data', [CandidateController::class, 'syncToHubSpot'])->name('candidates.push-data');
    Route::post('/candidates/{candidate}/generate-link', [CandidateController::class, 'generateLink'])->name('candidates.generate-link');
    Route::post('/sessions/{session}/send-email', [CandidateController::class, 'sendSessionEmail'])->name('sessions.send-email');
    Route::get('/sessions/{session}', [CandidateController::class, 'sessionDetail'])->name('sessions.show');
    Route::post('/sessions/{session}/grade', [CandidateController::class, 'gradeAnswer'])->name('sessions.grade');
    Route::post('/sessions/{session}/finalize', [CandidateController::class, 'finalizeSession'])->name('sessions.finalize');
    Route::post('/sessions/{session}/recalculate', [CandidateController::class, 'recalculateScore'])->name('sessions.recalculate');
});

require __DIR__.'/auth.php';
