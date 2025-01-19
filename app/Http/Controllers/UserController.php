<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;


use Illuminate\Support\Facades\DB;


class UserController extends Controller
{
    //Mostrar los usuarios
    public function index()
    {
        $user = auth()->user();
        $roleUser = $user->roles->first()->name;

        $users = User::join('grades as G', 'users.grade', '=', 'G.code')
            ->join('model_has_roles as MR', 'MR.model_id', '=', 'users.id')
            ->join('roles as Ro', 'MR.role_id', '=', 'Ro.id')
            ->select('users.id', 'Ro.name as role', 'users.name', 'users.email', 'users.is_active', 'G.grade_name as grade')
            ->get();

        return view('users.index', compact('users'));
    }

    //Mostrar el formulario para editar un usuario
    public function edit($id)
    {
        $user = User::find($id);
        $grades = DB::table('grades')->get();
        $roles = DB::table('roles')->get();

        $roleId = DB::table('model_has_roles')->where('model_id', $id)->first()->role_id;

        return view('users.edit', compact('user', 'grades', 'roles', 'roleId'));
    }

    //Actualizar un usuario
    public function update(Request $request)
    {
        try {
            $id = $request->id;
            $user = User::find($id);
            $user->fill($request->all());
            $user->save();

            //Actualiza el role_id en la tabla 'model_has_roles'
            DB::table('model_has_roles')->where('model_id', $id)->update(['role_id' => $request->role_id]);
            return redirect()->route('users.index')->with('success', 'Usuario modificado correctamente.');

        } catch (\Exception $e) {
            return redirect()->route('users.index')->with('error', 'Error al modificar el usuario.');
        }
    }

}
