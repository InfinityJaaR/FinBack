<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;

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
    });

    Route::middleware('role:Analista Financiero')->group(function () {
        // Rutas específicas para Analista Financiero
    });

    Route::middleware('role:Inversor')->group(function () {
        // Rutas específicas para Inversor
    });
});

