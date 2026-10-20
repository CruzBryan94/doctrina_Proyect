<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;



Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:10,1');
});

// RUTA PROTEGIDA PARA EL DASHBOARD
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->middleware('can:home')->name('dashboard');

    //RUTAS PARA GENERAR PDF
    Route::get('/generatePDFPrubea', [App\Http\Controllers\DashboardController::class, 'generatePDFPrubea'])->name('generatePDFPrubea');
    Route::get('/generatePDFManual', [App\Http\Controllers\DashboardController::class, 'generatePDFManual'])->name('dashboard.generatePDFManual');

    //RUTAS PARA MANUALES
    Route::get('/manuals', [App\Http\Controllers\ManualController::class, 'index'])->middleware('can:home')->name('manuals.index');
    Route::get('/manuals/newManual', [App\Http\Controllers\ManualController::class, 'newManual'])->middleware('can:home')->name('manuals.newManual');
    Route::get('/manuals/editOne/{id}', [App\Http\Controllers\ManualController::class, 'editOneManual'])->middleware('can:home')->name('manuals.editOneManual');
    Route::post('/manuals/store', [App\Http\Controllers\ManualController::class, 'store'])->name('manuals.store');
    Route::get('/manuals/editManual/{id}', [App\Http\Controllers\ManualController::class, 'editManual'])->middleware('can:home')->name('manuals.editManual');
    Route::put('/manuals/update/{id}', [App\Http\Controllers\ManualController::class, 'update'])->name('manuals.update');
    Route::put('/manuals/updateOneManual{id}', [App\Http\Controllers\ManualController::class, 'updateOneManual'])->name('manuals.updateOneManual');
    //FIN RUTAS DE MANUALES


    //RUTAS PARA USUARIOS
    Route::get('/users', [App\Http\Controllers\UserController::class, 'index'])->middleware('can:admin')->name('users.index');
    Route::post('/users/edit/{id}', [App\Http\Controllers\UserController::class, 'edit'])->middleware('can:admin')->name('users.edit');
    Route::post('/users/update', [App\Http\Controllers\UserController::class, 'update'])->middleware('can:admin')->name('users.update');
    //FIN RUTAS DE USUARIOS


    //RUTAS PARA MIEMBROS DE COMITES
    Route::get('/members', [App\Http\Controllers\MembersController::class, 'index'])->middleware('can:home')->name('members.index');
    Route::post('/members/edit/{id}', [App\Http\Controllers\MembersController::class, 'edit'])->middleware('can:home')->name('members.edit');
    Route::post('/members/update', [App\Http\Controllers\MembersController::class, 'update'])->middleware('can:home')->name('members.update');
    Route::get('/members/newMember', [App\Http\Controllers\MembersController::class, 'newMember'])->middleware('can:home')->name('members.newMember');
    Route::post('/members/store', [App\Http\Controllers\MembersController::class, 'store'])->name('members.store');
    //FIN RUTAS DE MIEMBROS DE COMITES

    //RUTAS PARA UNIDADES MILITARES
    Route::get('/militaryUnits', [App\Http\Controllers\MilitaryUnitController::class, 'index'])->middleware('can:home')->name('militaryUnits.index');
    Route::post('/militaryUnits/edit/{id}', [App\Http\Controllers\MilitaryUnitController::class, 'edit'])->middleware('can:home')->name('militaryUnits.edit');
    Route::post('/militaryUnits/update', [App\Http\Controllers\MilitaryUnitController::class, 'update'])->middleware('can:home')->name('militaryUnits.update');
    Route::get('/militaryUnits/newMilitaryUnit', [App\Http\Controllers\MilitaryUnitController::class, 'newMilitaryUnit'])->middleware('can:home')->name('militaryUnits.newMilitaryUnit');
    Route::post('/militaryUnits/store', [App\Http\Controllers\MilitaryUnitController::class, 'store'])->name('militaryUnits.store');
    //FIN RUTAS DE UNIDADES MILITARES




    Route::get('/', function () {
        return view('welcome');
    })->name('welcome');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});


// Redirige a /login si el usuario no está autenticado
Route::fallback(function () {
    return redirect('/login');
});
