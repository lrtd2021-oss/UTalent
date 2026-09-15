@extends('layouts.admin')

@section('titulo', 'Fuentes')

@section('contenido')
    <h1 class="text-xl font-bold text-slate-800 mb-6">Fuentes</h1>

    <form id="form-fuente" class="bg-white border border-slate-200 rounded-lg p-4 mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4 items-end">
        <input type="hidden" name="id">

        <div id="campo-nombre">
            <label class="block text-xs font-medium text-slate-500 mb-1">Nombre técnico</label>
            <input name="nombre" required class="w-full rounded-md border-slate-300 text-sm" placeholder="ej: computrabajo">
        </div>

        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Nombre visible</label>
            <input name="nombre_visible" required class="w-full rounded-md border-slate-300 text-sm" placeholder="ej: Computrabajo Uruguay">
        </div>

        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Tipo</label>
            <select name="tipo" class="w-full rounded-md border-slate-300 text-sm">
                <option value="publico">Público</option>
                <option value="privado">Privado</option>
            </select>
        </div>

        <div class="flex items-center gap-4">
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="activa" checked class="rounded border-slate-300 text-accent focus:ring-accent">
                Activa
            </label>
            <button type="submit" id="btn-guardar-fuente" class="bg-primary text-white text-sm font-semibold px-4 py-2 rounded-md">Crear fuente</button>
            <button type="button" id="btn-cancelar-fuente" hidden class="text-sm text-slate-500 underline">Cancelar</button>
        </div>
    </form>

    <p id="mensaje-fuente" class="text-sm mb-4"></p>

    <div class="bg-white border border-slate-200 rounded-lg overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                <tr>
                    <th class="px-4 py-2">Nombre técnico</th>
                    <th class="px-4 py-2">Nombre visible</th>
                    <th class="px-4 py-2">Tipo</th>
                    <th class="px-4 py-2">Estado</th>
                    <th class="px-4 py-2">Acciones</th>
                </tr>
            </thead>
            <tbody id="tabla-fuentes"></tbody>
        </table>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/admin-fuentes.js'])
@endpush
