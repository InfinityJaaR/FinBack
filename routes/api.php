<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RubroController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\RatioDefinicionController;
use App\Http\Controllers\CatalogoCuentaController;
use App\Http\Controllers\PeriodoController;
use App\Http\Controllers\EstadoFinancieroController;
use App\Http\Controllers\AnalisisBalanceController;

Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::post('/set-password', [AuthController::class, 'setPassword'])->name('set-password');

// Ruta pública: lista de categorías de ratios (no requiere autenticación)
Route::get('/ratios/categorias', [RatioDefinicionController::class, 'categorias']);

// Route::get('/empresas/{empresa}/ratios', [RatioDefinicionController::class, 'valoresPorPeriodo']);
// Route::post('/empresas/{empresa}/ratios/generar', [RatioDefinicionController::class, 'generarPorPeriodo']);

Route::middleware(['auth:sanctum'])->group(function () {

    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/periodos', [PeriodoController::class, 'index'])
    ->middleware('auth:sanctum');

    
    // RUTAS DE CÁLCULO Y CONSULTA DE RATIOS POR EMPRESA (para Analista/Admin)
    Route::middleware('role:Administrador')->group(function () {
        Route::middleware('permiso:manage_users')->group(function () {
            Route::get('/users', [UserController::class, 'index']);
            Route::post('/users', [UserController::class, 'store']);
            Route::get('/users/{id}', [UserController::class, 'show']);
            Route::put('/users/{id}', [UserController::class, 'update']);
            Route::delete('/users/{id}', [UserController::class, 'destroy']);
        });

        //     // --- Ratios por empresa (protegidas) ---
        // Route::middleware('permiso:ver_ratios')->get(
        //     '/empresas/{empresa}/ratios',
        //     [RatioDefinicionController::class, 'valoresPorPeriodo']
        // );

        // Route::middleware('permiso:calcular_ratios')->post(
        //     '/empresas/{empresa}/ratios/generar',
        //     [RatioDefinicionController::class, 'generarPorPeriodo']
        // );

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

        Route::middleware(['auth:sanctum','role:Administrador','permiso:ver_ratios'])->group(function () {
        Route::get('/benchmark/sector-ratio', [\App\Http\Controllers\BenchmarkController::class, 'sectorRatio']);
        Route::get('/rubros/{rubro}/empresas', [\App\Http\Controllers\EmpresaController::class, 'porRubro']); // opcional para poblar select
        });



            // Ver ratios (permiso ver_ratios)
        Route::middleware(['permiso:ver_ratios'])->group(function () {
            Route::get('/empresas/{empresa}/ratios', [RatioDefinicionController::class, 'valoresPorPeriodo']);
                // Listado de categorías permitidas para clasificar ratios
                Route::get('/ratios/categorias', [RatioDefinicionController::class, 'categorias']);
        });

        // Generar ratios (permiso calcular_ratios)
        Route::middleware(['permiso:calcular_ratios'])->group(function () {
            Route::post('/empresas/{empresa}/ratios/generar', [RatioDefinicionController::class, 'generarPorPeriodo']);
            // Calcular un único ratio (dry-run + breakdown)
            Route::get('/ratios/definiciones/{ratio_definicion}/calculate', [RatioDefinicionController::class, 'calculate']);
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
            
            // DESACTIVAR / ACTIVAR una empresa (Disable/Enable)
            Route::patch('/empresas/{empresa}/disable', [EmpresaController::class, 'disable'])->name('empresas.disable');
            
            // LISTAR usuarios de una empresa
            Route::get('/empresas/{empresa}/usuarios', [EmpresaController::class, 'usuarios'])->name('empresas.usuarios');
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

    Route::middleware('role:Administrador,Analista Financiero')->group(function () {
        
        // ----------------------------------------------------------------------
        // GRUPO DE RUTAS: GESTIÓN DE CATÁLOGO DE CUENTAS
        // Permiso requerido: 'gestionar_catalogo_cuentas'
        // ----------------------------------------------------------------------
        Route::middleware('permiso:gestionar_catalogo_cuentas')->group(function () {
            // OBTENER lista de empresas con información de catálogo
            Route::get('/catalogo-cuentas/empresas', [CatalogoCuentaController::class, 'empresasConCatalogo'])->name('catalogo.empresas');
            
            // OBTENER catálogo de cuentas de una empresa específica
            Route::get('/catalogo-cuentas/empresa/{empresaId}', [CatalogoCuentaController::class, 'index'])->name('catalogo.index');
            
            // CARGAR/REEMPLAZAR catálogo completo de una empresa
            Route::post('/catalogo-cuentas', [CatalogoCuentaController::class, 'store'])->name('catalogo.store');
            
            // ACTUALIZAR una cuenta específica
            Route::put('/catalogo-cuentas/{id}', [CatalogoCuentaController::class, 'update'])->name('catalogo.update');
            
            // ELIMINAR una cuenta específica
            Route::delete('/catalogo-cuentas/{id}', [CatalogoCuentaController::class, 'destroy'])->name('catalogo.destroy');
        });

        // ----------------------------------------------------------------------
        // GRUPO DE RUTAS: GESTIÓN DE ESTADOS FINANCIEROS
        // Permiso requerido: 'gestionar_catalogo_cuentas' (puede usar el mismo o crear uno nuevo)
        // ----------------------------------------------------------------------
        Route::middleware('permiso:gestionar_catalogo_cuentas')->group(function () {
            // OBTENER lista de empresas con catálogo
            Route::get('/estados-financieros/empresas', [EstadoFinancieroController::class, 'obtenerEmpresas'])->name('estados.empresas');
            
            // OBTENER lista de periodos disponibles
            Route::get('/estados-financieros/periodos', [EstadoFinancieroController::class, 'obtenerPeriodos'])->name('estados.periodos');
            
            // DESCARGAR plantilla CSV con cuentas según tipo de estado
            Route::get('/estados-financieros/plantilla', [EstadoFinancieroController::class, 'descargarPlantilla'])->name('estados.plantilla');
            
            // LISTAR estados financieros (con filtros opcionales)
            Route::get('/estados-financieros', [EstadoFinancieroController::class, 'index'])->name('estados.index');
            
            // OBTENER un estado financiero específico
            Route::get('/estados-financieros/{id}', [EstadoFinancieroController::class, 'show'])->name('estados.show');
            
            // CREAR nuevo estado financiero
            Route::post('/estados-financieros', [EstadoFinancieroController::class, 'store'])->name('estados.store');
            
            // ACTUALIZAR estado financiero
            Route::put('/estados-financieros/{id}', [EstadoFinancieroController::class, 'update'])->name('estados.update');
            
            // ELIMINAR estado financiero
            Route::delete('/estados-financieros/{id}', [EstadoFinancieroController::class, 'destroy'])->name('estados.destroy');
        });
    });

    // ----------------------------------------------------------------------
    // GRUPO DE RUTAS: ANÁLISIS DE BALANCE (Vertical y Horizontal)
    // Permiso requerido: 'analizar_balance'
    // - Analista: empresa_id se infiere desde su usuario
    // - Administrador: empresa_id viene desde el front
    // ----------------------------------------------------------------------
    Route::middleware(['permiso:analizar_balance'])->group(function () {
        Route::get('/analisis/balance/vertical', [AnalisisBalanceController::class, 'vertical']);
        Route::get('/analisis/balance/horizontal', [AnalisisBalanceController::class, 'horizontal']);
    });

    Route::middleware('role:Analista Financiero')->group(function () {
        // Rutas específicas para Analista Financiero
    });

    Route::middleware('role:Inversor')->group(function () {
        // Rutas específicas para Inversor
    });
});
