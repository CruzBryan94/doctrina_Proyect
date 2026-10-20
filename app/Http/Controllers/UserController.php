<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['roles', 'gradeRecord'])->orderBy('name')->paginate(25);

        return view('users.index', compact('users'));
    }

    public function edit(int $id)
    {
        return view('users.edit', [
            'user' => User::with('roles')->findOrFail($id), 'grades' => Grade::orderBy('code')->get(),
            'roles' => Role::where('guard_name', 'web')->get(),
        ]);
    }

    public function update(Request $request, int $id)
    {
        if (! $request->input('roles')) {
            $request->merge(['roles' => []]);
        }
        $data = $request->validate([
            'roles' => ['present', 'array'], 'roles.*' => ['string', 'distinct', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'grade' => ['nullable', 'integer', 'exists:grades,id'], 'is_active' => ['required', 'boolean'],
        ]);
        DB::transaction(function () use ($id, $data) {
            $user = User::lockForUpdate()->findOrFail($id);
            $roles = array_values(array_diff($data['roles'], ['none']));
            if (! $roles) {
                $roles = ['none'];
            }
            if ($user->hasRole('admin') && (! in_array('admin', $roles, true) || ! $data['is_active'])) {
                abort_if(User::role('admin')->where('is_active', true)->where('id', '!=', $id)->count() === 0, 422, 'Debe quedar al menos un administrador activo.');
            }
            $user->update(['grade' => $data['grade'] ?? null, 'is_active' => $data['is_active']]);
            $user->syncRoles($roles);
        });

        return redirect()->route('users.index')->with('success', 'Roles y estado actualizados.');
    }
}
