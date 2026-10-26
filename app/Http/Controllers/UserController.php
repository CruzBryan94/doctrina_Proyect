<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $this->authorizeAdmin(request());
        $users = User::with(['roles', 'gradeRecord'])->orderBy('name')->paginate(25);
        return view('users.index', compact('users'));
    }

    public function create()
    {
        $this->authorizeAdmin(request());
        return view('users.create', ['grades' => Grade::orderBy('code')->get(), 'roles' => Role::where('guard_name', 'web')->where('name', '!=', 'none')->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin($request);
        $this->normalizeEmail($request);
        if (! $request->input('roles')) $request->merge(['roles' => []]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'grade' => ['nullable', 'integer', 'exists:grades,id'], 'is_active' => ['required', 'boolean'],
            'roles' => ['present', 'array'], 'roles.*' => ['string', 'distinct', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ]);

        $user = DB::transaction(function () use ($request, $data) {
            $this->lockActiveAdmins();
            $actor = $this->lockAndAuthorizeAdmin($request);
            $user = User::create([
                'name' => $data['name'], 'email' => $data['email'], 'grade' => $data['grade'] ?? null,
                'is_active' => $data['is_active'], 'password' => Hash::make($data['password']), 'must_change_password' => false,
            ]);
            $roles = $this->normalizedRoles($data['roles']);
            $user->syncRoles($roles);
            $this->audit($actor->id, $user->id, 'account_created', null, $this->safeAccountData($user));
            return $user;
        });

        return redirect()->route('users.edit', $user)->with('success', 'Cuenta creada.');
    }

    public function edit(int $id)
    {
        $this->authorizeAdmin(request());
        return view('users.edit', [
            'user' => User::with('roles')->findOrFail($id), 'grades' => Grade::orderBy('code')->get(),
            'roles' => Role::where('guard_name', 'web')->get(),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $this->authorizeAdmin($request);
        $this->normalizeEmail($request);
        if ($request->has('roles') && ($request->input('roles') === '' || $request->input('roles') === null)) {
            $request->merge(['roles' => []]);
        }
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'roles' => ['sometimes', 'array'], 'roles.*' => ['string', 'distinct', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'grade' => ['sometimes', 'nullable', 'integer', 'exists:grades,id'], 'is_active' => ['sometimes', 'required', 'boolean'],
        ]);
        DB::transaction(function () use ($request, $id, $data) {
            $admins = $this->lockActiveAdmins();
            $actor = $this->lockAndAuthorizeAdmin($request);
            $user = User::lockForUpdate()->findOrFail($id);
            $before = $this->safeAccountData($user);
            $roles = array_key_exists('roles', $data) ? $this->normalizedRoles($data['roles']) : null;
            if ($roles !== null && $user->hasRole('admin') && (! in_array('admin', $roles, true) || (array_key_exists('is_active', $data) && ! $data['is_active']))) {
                abort_if($admins->where('id', '!=', $user->id)->isEmpty(), 422, 'Debe quedar al menos un administrador activo.');
            }
            if ($roles === null && $user->hasRole('admin') && array_key_exists('is_active', $data) && ! $data['is_active']) {
                abort_if($admins->where('id', '!=', $user->id)->isEmpty(), 422, 'Debe quedar al menos un administrador activo.');
            }
            $changes = array_intersect_key($data, array_flip(['name', 'email', 'grade', 'is_active']));
            if ($changes) $user->update($changes);
            if ($roles !== null && $user->getRoleNames()->sort()->values()->all() !== collect($roles)->sort()->values()->all()) {
                $user->syncRoles($roles);
                $user->unsetRelation('roles');
                $user->load('roles');
            }
            $after = $this->safeAccountData($user);
            if ($before !== $after) $this->audit($actor->id, $user->id, 'account_updated', $before, $after);
        });
        return redirect()->route('users.index')->with('success', 'Cuenta actualizada.');
    }

    public function resetPassword(Request $request, int $id)
    {
        DB::transaction(function () use ($request, $id) {
            $this->lockActiveAdmins();
            $actor = $this->lockAndAuthorizeAdmin($request);
            $user = User::lockForUpdate()->findOrFail($id);
            abort_unless($user->is_active, 422, 'No se puede habilitar una cuenta inactiva.');
            $user->password = Hash::make('132456');
            $user->must_change_password = true;
            $user->save();
            $this->audit($actor->id, $user->id, 'password_reset_enabled');
        });
        return redirect()->route('users.edit', $id)->with('success', 'Contraseña provisional habilitada.');
    }

    private function normalizeEmail(Request $request): void
    {
        if ($request->filled('email')) $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
    }

    private function normalizedRoles(array $roles): array
    {
        $roles = array_values(array_diff($roles, ['none']));
        return $roles ?: ['none'];
    }

    private function lockAndAuthorizeAdmin(Request $request): User
    {
        // Active admin rows are locked in ascending ID order by the caller before this check.
        $actor = User::lockForUpdate()->findOrFail($request->user()->id);
        abort_unless($actor->is_active && $actor->hasRole('admin'), 403, 'Se requiere una cuenta activa con rol administrador.');
        return $actor;
    }

    private function authorizeAdmin(Request $request): User
    {
        $actor = User::findOrFail($request->user()->id);
        abort_unless($actor->is_active && $actor->hasRole('admin'), 403, 'Se requiere una cuenta activa con rol administrador.');
        return $actor;
    }

    private function lockActiveAdmins()
    {
        // Serialize admin-set changes on the stable role row before reading the changing assignment set.
        Role::query()->where('name', 'admin')->where('guard_name', 'web')->lockForUpdate()->firstOrFail();

        return User::role('admin')->where('is_active', true)->orderBy('id')->lockForUpdate()->get(['users.id']);
    }

    private function safeAccountData(User $user): array
    {
        return [
            'name' => $user->name, 'email' => $user->email, 'grade' => $user->grade,
            'is_active' => (bool) $user->is_active, 'roles' => $user->getRoleNames()->sort()->values()->all(),
        ];
    }

    private function audit(int $actorId, int $targetId, string $action, ?array $before = null, ?array $after = null): void
    {
        DB::table('account_access_audits')->insert([
            'actor_user_id' => $actorId, 'target_user_id' => $targetId, 'action' => $action,
            'before_data' => $before === null ? null : json_encode($before),
            'after_data' => $after === null ? null : json_encode($after), 'created_at' => now(),
        ]);
    }
}
