<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();
        // Obtiene el primer (y único) rol del usuario
        $rol = $user?->roles()->first();
        if (!$rol || !in_array($rol->name, $roles)) {
            return response()->json(['message' => 'No autorizado. Rol insuficiente.'], 403);
        }
        return $next($request);
    }
}
