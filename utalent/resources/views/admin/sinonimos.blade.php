@extends('layouts.admin')

@section('titulo', 'Sinónimos')

@section('contenido')
    <h1 class="text-xl font-bold text-slate-800 mb-6">Sinónimos de búsqueda</h1>

    <form id="form-sinonimo" class="bg-white border border-slate-200 rounded-lg p-4 mb-6 grid gap-3 sm:grid-cols-3 items-end">
        <input type="hidden" name="id">

        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Término</label>
            <input name="termino" required class="w-full rounded-md border-slate-300 text-sm" placeholder="ej: developer">
        </div>

        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Grupo</label>
            <input name="grupo" required class="w-full rounded-md border-slate-300 text-sm" placeholder="ej: programador">
        </div>

        <div class="flex gap-3">
            <button type="submit" id="btn-guardar-sinonimo" class="bg-primary text-white text-sm font-semibold px-4 py-2 rounded-md">Crear sinónimo</button>
            <button type="button" id="btn-cancelar-sinonimo" hidden class="text-sm text-slate-500 underline">Cancelar</button>
        </div>
    </form>

    <p id="mensaje-sinonimo" class="text-sm mb-4"></p>

    <div class="bg-white border border-slate-200 rounded-lg overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                <tr>
                    <th class="px-4 py-2">Término</th>
                    <th class="px-4 py-2">Grupo</th>
                    <th class="px-4 py-2">Acciones</th>
                </tr>
            </thead>
            <tbody id="tabla-sinonimos"></tbody>
        </table>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/admin-sinonimos.js'])
@endpush
