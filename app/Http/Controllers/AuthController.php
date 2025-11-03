<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'nullable|string|min:8',
            'role_id' => 'required|exists:roles,id',
            'empresa_id' => 'nullable|exists:empresas,id'
        ]);

        if($validator->fails()) {
            return response()->json($validator->errors());
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->filled('password') ? Hash::make($request->password) : null,
            'empresa_id' => $request->empresa_id
        ]);

        // Asignar rol
        $user->roles()->sync([$request->role_id]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'data' => $user->load(['roles', 'empresa']),
            'access_token' => $token,
            'token_type' => 'Bearer'
        ], 200);
    }

    public function login(Request $request)
    {
        $user = User::where('email', $request['email'])->first();

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        // Validar que el usuario esté activo
        if (!$user->active) {
            return response()->json(['message' => 'Usuario inactivo'], 403);
        }

        // Si el usuario no tiene contraseña, debe establecerla primero
        if (is_null($user->password)) {
            return response()->json([
                'message' => 'Debe establecer una contraseña',
                'require_password' => true,
                'id' => $user->id
            ], 200);
        }

        // Si no se envió la contraseña, solo responde que el usuario existe y requiere contraseña
        if (!$request->has('password')) {
            return response()->json([
                'message' => 'El usuario tiene contraseña, ingrésela para continuar',
                'require_password' => false,
                'id' => $user->id
            ], 200);
        }

        // Login normal
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Credenciales inválidas'], 401);
        }

        // Obtener permisos únicos del usuario a través de sus roles
        $permissions = $user->roles()
            ->with('permisos')
            ->get()
            ->pluck('permisos')
            ->flatten()
            ->pluck('name')
            ->unique()
            ->values();

        // Crear un token para el usuario
        $tokenInstance = $user->createToken('auth_token');
        $token = $tokenInstance->plainTextToken;
        
        // Almacenar la marca de tiempo inicial para el token en la caché
        $tokenParts = explode('|', $token);
        if (count($tokenParts) === 2) {
            $personalAccessToken = PersonalAccessToken::findToken($tokenParts[1]);
            if ($personalAccessToken) {
                $cacheKey = "token_last_activity_{$personalAccessToken->id}";
                Cache::put($cacheKey, Carbon::now()->toDateTimeString(), Carbon::now()->addMinutes(20));
            }
        }

        return response()->json([
            'message' => 'Inicio de sesión exitoso',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->load('roles'),
            'permissions' => $permissions,
        ], 200);
    }

    public function setPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:users,id',
            'password' => 'required|string|min:8',
        ]);

        if($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::find($request->id);

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        if (!is_null($user->password)) {
            return response()->json(['message' => 'La contraseña ya ha sido establecida'], 400);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json(['message' => 'Contraseña establecida correctamente'], 200);
    }

    public function logout(Request $request)
    {
        // Eliminar el registro de caché cuando se cierra sesión
        $token = $request->bearerToken();
        if ($token) {
            $personalAccessToken = PersonalAccessToken::findToken($token);
            if ($personalAccessToken) {
                $tokenId = $personalAccessToken->id;
                $cacheKey = "token_last_activity_{$tokenId}";
                $expiredKey = "token_expired_{$tokenId}";
                
                // Limpiar tanto la actividad como la marca de expirado
                Cache::forget($cacheKey);
                Cache::forget($expiredKey);
            }
        }
        
        $request->user()->tokens()->delete();
        return ['message' => 'Sesión cerrada exitosamente'];
    }
}
