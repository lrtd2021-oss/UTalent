@extends('layouts.app')

@section('titulo', 'Detalle de la oferta')

@section('contenido')
    <div id="detalle" data-oferta-id="{{ $id }}" class="max-w-5xl mx-auto px-4 py-6 sm:py-10">
        <a href="{{ url('/ofertas') }}" class="text-sm text-slate-500 hover:text-primary transition-colors">← Volver a resultados</a>

        <div id="estado-carga" class="mt-6 animate-pulse space-y-3">
            <div class="h-8 bg-slate-200 rounded w-2/3"></div>
            <div class="h-4 bg-slate-200 rounded w-1/3"></div>
            <div class="h-40 bg-slate-200 rounded"></div>
        </div>

        <div id="estado-error" hidden class="mt-6 text-center py-16 bg-white border border-red-200 rounded-lg">
            <p class="text-red-600 font-medium">No pudimos cargar esta oferta.</p>
            <button type="button" id="btn-reintentar" class="mt-4 bg-primary text-white text-sm font-semibold px-5 py-2 rounded-md">Reintentar</button>
        </div>

        <div id="estado-no-encontrada" hidden class="mt-6 text-center py-16 bg-white border border-slate-200 rounded-lg">
            <p class="text-slate-700 font-medium">Esta oferta no existe o fue eliminada.</p>
        </div>

        <div id="contenido-oferta" hidden class="mt-6 grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 bg-white border border-slate-200 rounded-lg p-6">
                <div id="aviso-cerrada" hidden class="mb-4 bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-md px-4 py-3">
                    Esta oferta ya no está disponible en la fuente original. La información se conserva a modo histórico.
                </div>

                <div id="badges" class="flex flex-wrap gap-2 mb-2"></div>
                <h1 id="titulo" class="text-2xl font-bold text-slate-900"></h1>
                <p id="empresa-departamento" class="text-slate-500 mt-1"></p>

                <div id="tecnologias" class="flex flex-wrap gap-1 mt-3"></div>

                <hr class="my-5 border-slate-200">

                <h2 class="font-semibold text-slate-700 mb-2">Descripción</h2>
                <p id="descripcion" class="text-slate-600 whitespace-pre-line"></p>
            </div>

            <aside class="bg-white border border-slate-200 rounded-lg p-6 h-fit lg:sticky lg:top-6 space-y-3 text-sm">
                <div>
                    <p class="text-slate-400">Salario</p>
                    <p id="salario" class="font-medium text-slate-700"></p>
                </div>
                <div>
                    <p class="text-slate-400">Publicada</p>
                    <p id="fecha-publicacion" class="font-medium text-slate-700"></p>
                </div>
                <div>
                    <p class="text-slate-400">Fuente</p>
                    <p id="fuente" class="font-medium text-slate-700"></p>
                </div>

                <a id="cta-original" href="#" target="_blank" rel="noopener noreferrer"
                    class="block text-center bg-accent text-primary font-semibold px-4 py-3 rounded-md hover:brightness-110 transition mt-2">
                    Ver publicación original ↗
                </a>
            </aside>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/oferta-detalle.js'])
@endpush
