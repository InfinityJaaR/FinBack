<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RubroController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\RatioDefinicionController;

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

        // ----------------------------------------------------------------------
        // GRUPO DE RUTAS: GESTIÓN DE RUBROS
        // Permiso requerido: 'gestionar_rubros'
        // ----------------------------------------------------------------------
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

        // ----------------------------------------------------------------------
        // GRUPO DE RUTAS: GESTIÓN DE EMPRESAS
        // Permiso requerido: 'gestionar_empresas'
        // ----------------------------------------------------------------------
        Route::middleware('permiso:gestionar_empresas')->group(function () {
            // LISTAR todas las empresas (Index)
            Route::get('/empresas', [EmpresaController::class, 'index'])->name('empresas.index');

            // CREAR una nueva empresa (Store)
            Route::post('/empresas', [EmpresaController::class, 'store'])->name('empresas.store');

            // OBTENER datos para el formulario de creación (Create) - Opcional en API
            Route::get('/empresas/create', [EmpresaController::class, 'create'])->name('empresas.create');

            // OBTENER una empresa específica (Show)
            // Usamos {empresa} para Route Model Binding.
            Route::get('/empresas/{empresa}', [EmpresaController::class, 'show'])->name('empresas.show');

            // OBTENER datos para el formulario de edición (Edit) - Opcional en API
            Route::get('/empresas/{empresa}/edit', [EmpresaController::class, 'edit'])->name('empresas.edit');

            // ACTUALIZAR una empresa (Update)
            Route::put('/empresas/{empresa}', [EmpresaController::class, 'update'])->name('empresas.update');

            // ELIMINAR una empresa (Destroy)
            Route::delete('/empresas/{empresa}', [EmpresaController::class, 'destroy'])->name('empresas.destroy');
        });

        // ----------------------------------------------------------------------
        // GRUPO DE RUTAS: DEFINICIÓN DE RATIOS
        // Permiso requerido: 'gestionar_ratios_definicion'
        // ----------------------------------------------------------------------
        Route::middleware('permiso:gestionar_ratios_definicion')->group(function () {
            // LISTAR todas las definiciones de ratios (Index)
            Route::get('/ratios/definiciones', [RatioDefinicionController::class, 'index'])->name('ratios.definiciones.index');

            // CREAR una nueva definición de ratio (Store)
            Route::post('/ratios/definiciones', [RatioDefinicionController::class, 'store'])->name('ratios.definiciones.store');

            // OBTENER datos para el formulario de creación (Create) - Opcional en API
            Route::get('/ratios/definiciones/create', [RatioDefinicionController::class, 'create'])->name('ratios.definiciones.create');

            // OBTENER una definición de ratio específica (Show)
            // Usamos {ratio_definicion} para Route Model Binding.
            Route::get('/ratios/definiciones/{ratio_definicion}', [RatioDefinicionController::class, 'show'])->name('ratios.definiciones.show');

            // OBTENER datos para el formulario de edición (Edit) - Opcional en API
            Route::get('/ratios/definiciones/{ratio_definicion}/edit', [RatioDefinicionController::class, 'edit'])->name('ratios.definiciones.edit');

            // ACTUALIZAR una definición de ratio (Update)
            Route::put('/ratios/definiciones/{ratio_definicion}', [RatioDefinicionController::class, 'update'])->name('ratios.definiciones.update');

            // ELIMINAR una definición de ratio (Destroy)
            Route::delete('/ratios/definiciones/{ratio_definicion}', [RatioDefinicionController::class, 'destroy'])->name('ratios.definiciones.destroy');
        });
    });

    Route::middleware('role:Analista Financiero')->group(function () {
        // Rutas específicas para Analista Financiero
    });

    Route::middleware('role:Inversor')->group(function () {
        // Rutas específicas para Inversor
    });
});
