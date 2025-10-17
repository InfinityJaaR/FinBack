<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Rol;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('roles')->whereDoesntHave('roles', function ($query) {
            $query->where('name', 'Administrador');
        });

        // Filtrar por estado activo/inactivo si se proporciona el parámetro
        // Si el parametro es 'todos' o no se proporciona, no se aplica filtro de estado
        if ($request->has('active') && $request->active !== 'todos') {
            $active = filter_var($request->active, FILTER_VALIDATE_BOOLEAN);
            $query->where('active', $active);
        }

        $users = $query->get();
        
        if ($users->isEmpty()) {
            return response()->json(['message' => 'No hay usuarios registrados'], 404);
        }

        return response()->json([
            'users' => $users
        ], 200);
    }

    public function show($id)
    {
        $user = User::with('roles')->find($id);

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        return response()->json([
            'user' => $user
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $validator = \Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
            'role_id' => 'sometimes|exists:roles,id',
            'active' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Actualizar campos básicos
        if ($request->has('name')) {
            $user->name = $request->name;
        }
        
        if ($request->has('email')) {
            $user->email = $request->email;
        }

        if ($request->has('role_id')) {
            $user->roles()->sync([$request->role_id]);
        }

        if ($request->has('active')) {
            $user->active = $request->active;
        }

        $user->save();

        return response()->json([
            'message' => 'Usuario actualizado correctamente',
            'user' => $user->load('roles')
        ], 200);
    }

    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        // Desactivar el usuario en lugar de eliminarlo
        $user->active = false;
        $user->save();
        return response()->json([
            'message' => 'Usuario desactivado correctamente',
            'user' => $user
        ], 200);
    }

    public function reactive($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        // Reactivar el usuario
        $user->active = true;
        $user->save();

        return response()->json([
            'message' => 'Usuario reactivado correctamente',
            'user' => $user
        ], 200);
    }
    
    public function eliminarUsuario($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        // Eliminar el usuario
        $user->delete();

        return response()->json([
            'message' => 'Usuario eliminado correctamente'
        ], 200);
    }
}
