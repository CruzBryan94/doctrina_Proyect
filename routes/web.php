<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ManualController;
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
Route::middleware('auth')->group(function () {
    Route::get('/', fn () => auth()->user()->can('dashboard.view') ? redirect()->route('dashboard') : view('welcome'))->name('welcome');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('can:dashboard.view')->name('dashboard');
    Route::get('/reports/manuals', [DashboardController::class, 'report'])->middleware('can:reports.view')->name('reports.manuals');
    Route::get('/generatePDFManual', fn () => redirect()->route('reports.manuals'))->middleware('can:reports.view')->name('dashboard.generatePDFManual');

    Route::get('/manuals', [ManualController::class, 'index'])->name('manuals.index');
    Route::get('/manuals/newManual', [ManualController::class, 'create'])->name('manuals.newManual');
    Route::post('/manuals/store', [ManualController::class, 'store'])->name('manuals.store');
    Route::get('/manuals/{id}/edit', [ManualController::class, 'edit'])->name('manuals.editOneManual');
    Route::put('/manuals/{id}', [ManualController::class, 'update'])->name('manuals.updateOneManual');
    Route::patch('/manuals/{id}/activity', [ManualController::class, 'activity'])->name('manuals.activity');
    Route::get('/manuals/{id}/history', [ManualController::class, 'history'])->name('manuals.history');

    Route::middleware('can:users.manage')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
    });
    Route::middleware('can:catalogs.manage')->group(function () {
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
