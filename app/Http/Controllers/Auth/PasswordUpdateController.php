<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordUpdateController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        abort_unless($request->user()->is_active, 403, 'La cuenta está inactiva.');
        if (! $request->user()->must_change_password) {
            return redirect()->route('welcome');
        }

        return view('auth.password-change-required');
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->is_active, 403, 'La cuenta está inactiva.');
        abort_unless($request->user()->must_change_password, 403);

        $validated = $request->validate([
            'password' => ['required', 'string', Password::defaults(), 'confirmed', function ($attribute, $value, $fail) {
                if ($value === '132456') $fail('Elija una contraseña distinta de la provisional.');
            }],
        ]);

        $user = DB::transaction(function () use ($request, $validated) {
            $user = $request->user()->newQuery()->lockForUpdate()->findOrFail($request->user()->id);
            abort_unless($user->is_active && $user->must_change_password, 403);
            $user->password = Hash::make($validated['password']);
            $user->must_change_password = false;
            $user->save();

            DB::table('account_access_audits')->insert([
                'actor_user_id' => $user->id, 'target_user_id' => $user->id,
                'action' => 'password_change_completed', 'created_at' => now(),
            ]);

            return $user;
        });

        $request->setUserResolver(fn () => $user);
        Auth::guard('web')->setUser($user);
        $request->session()->regenerate();

        return redirect()->route('welcome')->with('success', 'La contraseña se actualizó.');
    }
}
