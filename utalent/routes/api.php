<?php

use App\Http\Controllers\FuenteController;
use App\Http\Controllers\OfertaController;
use App\Http\Controllers\SinonimoController;
use Illuminate\Support\Facades\Route;

// Busqueda del usuario final: solo lee de la base local, nunca consulta
// fuentes externas ni despacha Jobs.
Route::get('ofertas', [OfertaController::class, 'index']);

// ABM del motor de busqueda: administracion de fuentes y sinonimos.
// No incluye "show" porque los controladores no lo implementan todavia.
Route::apiResource('fuentes', FuenteController::class)->only(['index', 'store', 'update', 'destroy']);
Route::apiResource('sinonimos', SinonimoController::class)->only(['index', 'store', 'update', 'destroy']);
