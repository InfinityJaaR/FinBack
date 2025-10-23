<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RubroController;

Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::post('/set-password', [AuthController::class, 'setPassword'])->name('set-password');

Route::middleware(['auth:sanctum'])->group(function () {

    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('role:Administrador')->group(function () {
        Route::middleware('permiso:manage_users')->group(function () {
            Route::get('/users', [UserController::class, 'index']);
            Route::post('/users', [UserController::class, 'store']);
            Route::get('/users/{id}', [UserController::class, 'show']);
            Route::put('/users/{id}', [UserController::class, 'update']);
            Route::delete('/users/{id}', [UserController::class, 'destroy']);
        });

        // Grupo de Rutas que SOLO permite el Permiso 'gestionar_rubros'
        Route::middleware('permiso:gestionar_rubros')->group(function () {
            // 1. OBTENER todos los rubros (Index)
            Route::get('/rubros', [RubroController::class, 'index'])->name('rubros.index');
            // 2. CREAR un nuevo rubro (Store)
            Route::post('/rubros', [RubroController::class, 'store'])->name('rubros.store');
            // 3. OBTENER un rubro específico (Show)
            // Usamos {rubro} para que Laravel sepa hacer el Route Model Binding con el modelo Rubro.
            Route::get('/rubros/{rubro}', [RubroController::class, 'show'])->name('rubros.show');
            // 4. ACTUALIZAR un rubro (Update)
            // Se usa PUT para la actualización completa del recurso.
            Route::put('/rubros/{rubro}', [RubroController::class, 'update'])->name('rubros.update');
            // NOTA: También podrías usar Route::patch() si solo permitieras actualizaciones parciales.
            // 5. ELIMINAR un rubro (Destroy)
            Route::delete('/rubros/{rubro}', [RubroController::class, 'destroy'])->name('rubros.destroy');
        });
    });

    Route::middleware('role:Analista Financiero')->group(function () {
        // Rutas específicas para Analista Financiero
    });

    Route::middleware('role:Inversor')->group(function () {
        // Rutas específicas para Inversor
    });
});
