<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Empresa;

class EmpresaAccessMiddleware
{
    /**
     * Handle an incoming request.
     * 
     * Este middleware verifica que el usuario tenga acceso a la empresa solicitada.
     * - Los Administradores tienen acceso a todas las empresas
     * - Los usuarios con empresa_id solo pueden acceder a su propia empresa
     * - Los usuarios sin empresa_id no pueden acceder a ninguna empresa específica
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        // Si no hay usuario autenticado, rechazar
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado.'
            ], 401);
        }

        // Verificar si el usuario tiene rol de Administrador
        $esAdministrador = $user->roles()->where('name', 'Administrador')->exists();
        
        // Los administradores tienen acceso a todas las empresas
        if ($esAdministrador) {
            return $next($request);
        }

        // Obtener el ID de empresa de la ruta (puede venir como parámetro empresa o empresa_id)
        $empresaId = $request->route('empresa') 
            ? ($request->route('empresa') instanceof Empresa 
                ? $request->route('empresa')->id 
                : $request->route('empresa'))
            : $request->route('empresa_id');

        // Si no hay empresa en la ruta, permitir continuar
        if (!$empresaId) {
            return $next($request);
        }

        // Verificar que el usuario tenga una empresa asignada
        if (!$user->empresa_id) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes una empresa asignada.'
            ], 403);
        }

        // Verificar que la empresa solicitada sea la misma que la del usuario
        if ($user->empresa_id != $empresaId) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes acceso a esta empresa.'
            ], 403);
        }

        return $next($request);
    }
}
