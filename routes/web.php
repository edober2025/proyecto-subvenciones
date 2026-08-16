<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('dashboard.token')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/upload', [DashboardController::class, 'upload'])->name('upload');
    Route::get('/resumen', [DashboardController::class, 'resumen'])->name('resumen');
    Route::get('/detalle', [DashboardController::class, 'detalle'])->name('detalle');
    Route::get('/grafico', [DashboardController::class, 'grafico'])->name('grafico');
    Route::get('/meses-disponibles', [DashboardController::class, 'mesesDisponibles'])->name('meses.disponibles');
    Route::get('/evolucion', [DashboardController::class, 'evolucion'])->name('evolucion');
});
