<?php

use App\Http\Controllers\FuenteController;
use App\Http\Controllers\SinonimoController;
use Illuminate\Support\Facades\Route;

// ABM del motor de busqueda: administracion de fuentes y sinonimos.
// No incluye "show" porque los controladores no lo implementan todavia.
Route::apiResource('fuentes', FuenteController::class)->only(['index', 'store', 'update', 'destroy']);
Route::apiResource('sinonimos', SinonimoController::class)->only(['index', 'store', 'update', 'destroy']);
