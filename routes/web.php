<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\PasswordUpdateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ManualController;
use App\Http\Controllers\ManualDossierController;
use App\Http\Controllers\ManualPhaseNoteController;
use App\Http\Controllers\PhaseDurationController;
use App\Http\Controllers\ProjectionController;
use App\Http\Controllers\InstitutionalReferenceController;
use App\Http\Controllers\HistoricalDashboardController;
use App\Http\Controllers\MembersController;
use App\Http\Controllers\MilitaryUnitController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::pattern('id', '[0-9]+');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:10,1');
});
Route::middleware(['auth', \App\Http\Middleware\EnsureActiveAccount::class, \App\Http\Middleware\EnsurePasswordUpdated::class])->group(function () {
    Route::get('/password/change', [PasswordUpdateController::class, 'show'])->name('password.change.show');
    Route::put('/password/change', [PasswordUpdateController::class, 'update'])->name('password.change.update');
    Route::get('/', fn () => auth()->user()->can('dashboard.view') ? redirect()->route('dashboard') : view('welcome'))->name('welcome');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('can:dashboard.view')->name('dashboard');
    Route::get('/dashboard/history', [HistoricalDashboardController::class, 'index'])->middleware('can:dashboard.view')->name('dashboard.history');
    Route::get('/dashboard/schedule', [ProjectionController::class, 'index'])->middleware('can:dashboard.view')->name('dashboard.schedule');
    Route::get('/references/institutional', [InstitutionalReferenceController::class, 'index'])->name('references.index');
    Route::put('/admin/references/institutional/{doctrinalMap}', [InstitutionalReferenceController::class, 'update'])->whereNumber('doctrinalMap')->middleware('can:users.manage')->name('references.update');
    Route::get('/admin/phase-durations', [PhaseDurationController::class, 'index'])->middleware('can:users.manage')->name('phase-durations.index');
    Route::put('/admin/phase-durations/{manualPhase}', [PhaseDurationController::class, 'update'])->whereNumber('manualPhase')->middleware('can:users.manage')->name('phase-durations.update');
    Route::get('/reports/history', [HistoricalDashboardController::class, 'report'])->middleware('can:reports.view')->name('reports.history');
    Route::get('/reports/manuals', [DashboardController::class, 'report'])->middleware('can:reports.view')->name('reports.manuals');
    Route::get('/generatePDFManual', fn () => redirect()->route('reports.manuals'))->middleware('can:reports.view')->name('dashboard.generatePDFManual');

    Route::get('/manuals', [ManualController::class, 'index'])->name('manuals.index');
    Route::get('/manuals/newManual', [ManualController::class, 'create'])->name('manuals.newManual');
    Route::get('/manuals/assignment-options', [ManualController::class, 'newAssignmentOptions'])->name('manuals.newAssignmentOptions');
    Route::post('/manuals/store', [ManualController::class, 'store'])->name('manuals.store');
    Route::get('/manuals/{id}/edit', [ManualController::class, 'edit'])->name('manuals.editOneManual');
    Route::get('/manuals/{id}/assignment-options', [ManualController::class, 'assignmentOptions'])->name('manuals.assignmentOptions');
    Route::get('/manuals/{id}/responsible-options', [ManualController::class, 'responsibleOptions'])->name('manuals.responsibleOptions');
    Route::put('/manuals/{id}', [ManualController::class, 'update'])->name('manuals.updateOneManual');
    Route::patch('/manuals/{id}/activity', [ManualController::class, 'activity'])->name('manuals.activity');
    Route::get('/manuals/{id}/history', [ManualController::class, 'history'])->name('manuals.history');
    Route::get('/manuals/{manualId}/phases/{phaseId}/note', [ManualPhaseNoteController::class, 'show'])->whereNumber(['manualId', 'phaseId'])->name('manuals.phase-notes.show');
    Route::patch('/manuals/{manualId}/phases/{phaseId}/note', [ManualPhaseNoteController::class, 'update'])->whereNumber(['manualId', 'phaseId'])->name('manuals.phase-notes.update');
    Route::get('/manuals/{id}', [ManualDossierController::class, 'show'])->name('manuals.show');

    Route::middleware('can:users.manage')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::post('/users/{id}/password-reset', [UserController::class, 'resetPassword'])->name('users.resetPassword');
        Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
    });
    Route::middleware('can:catalogs.manage')->group(function () {
        Route::get('/members/import', [MembersController::class, 'importForm'])->name('members.import');
        Route::post('/members/import/preview', [MembersController::class, 'previewImport'])->name('members.import.preview');
        Route::post('/members/import', [MembersController::class, 'commitImport'])->name('members.import.commit');
        Route::get('/members', [MembersController::class, 'index'])->name('members.index');
        Route::get('/members/newMember', [MembersController::class, 'newMember'])->name('members.newMember');
        Route::post('/members/store', [MembersController::class, 'store'])->name('members.store');
        Route::post('/members/edit/{id}', [MembersController::class, 'edit'])->name('members.edit');
        Route::post('/members/update', [MembersController::class, 'update'])->name('members.update');
        Route::get('/militaryUnits', [MilitaryUnitController::class, 'index'])->name('militaryUnits.index');
        Route::get('/militaryUnits/newMilitaryUnit', [MilitaryUnitController::class, 'newMilitaryUnit'])->name('militaryUnits.newMilitaryUnit');
        Route::post('/militaryUnits/store', [MilitaryUnitController::class, 'store'])->name('militaryUnits.store');
        Route::post('/militaryUnits/edit/{id}', [MilitaryUnitController::class, 'edit'])->name('militaryUnits.edit');
        Route::post('/militaryUnits/update', [MilitaryUnitController::class, 'update'])->name('militaryUnits.update');
    });
});
