<?php

use Illuminate\Support\Facades\Route;

// Estas vistas solo entregan la estructura de la pagina: los datos de
// ofertas/fuentes/sinonimos los carga el JS de cada una contra /api/*.
Route::get('/', fn () => view('home'));
Route::get('/ofertas', fn () => view('ofertas.index'));
Route::get('/ofertas/{id}', fn (string $id) => view('ofertas.show', ['id' => $id]))->where('id', '[0-9]+');

Route::get('/admin/fuentes', fn () => view('admin.fuentes'));
Route::get('/admin/sinonimos', fn () => view('admin.sinonimos'));
