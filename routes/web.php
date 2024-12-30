<?php

use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;



// Ruta para mostrar la vista de login
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

// RUTAS PARA LOGIN CON GOOGLE
Route::prefix('google-auth')->group(function () {
    // Redirige al proveedor de Google
    Route::get('/redirect', function () {
        return Socialite::driver('google')->redirect();
    })->name('google.redirect');

    // Callback después de autenticarse en Google
    Route::get('/callback', function () {
        $user_google = Socialite::driver('google')->stateless()->user();

        $user = User::updateOrCreate(
            ['google_id' => $user_google->id],
            [
                'name' => $user_google->name,
                'email' => $user_google->email,
                'is_admin' => false,
                'is_active' => false,
                'grade' => 17
            ]
        );

        // Inicia sesión con el usuario autenticado
        Auth::login($user);

        return redirect('/'); // Redirige al dashboard
    })->name('google.callback');
});

// RUTA PROTEGIDA PARA EL DASHBOARD
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('welcome');
    })->name('dashboard');


    Route::get('/', function () {
        return view('welcome');
    })->name('welcome');

    // Ruta para cerrar sesión
    Route::post('/logout', function () {
        Auth::logout();
        return redirect('/login'); // Redirige a la página de login después de cerrar sesión
    })->name('logout');


});








// Redirige a /login si el usuario no está autenticado
Route::fallback(function () {
    return redirect('/login');
});
