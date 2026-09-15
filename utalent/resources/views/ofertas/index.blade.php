@extends('layouts.app')

@section('titulo', 'Buscar ofertas')

@section('contenido')
    <div class="max-w-5xl mx-auto px-4 py-6 sm:py-10">
        <h1 class="text-2xl font-bold text-primary mb-6">Buscar ofertas</h1>

        <details id="filtros-detalle" class="bg-white border border-slate-200 rounded-lg mb-6" open>
            <summary class="cursor-pointer select-none px-4 py-3 font-medium text-slate-700 sm:hidden">
                Filtros
            </summary>

            <form id="form-filtros" class="p-4 pt-0 sm:pt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <div class="lg:col-span-2">
                    <label for="q" class="block text-xs font-medium text-slate-500 mb-1">Puesto o tecnología</label>
                    <input type="text" id="q" name="q" placeholder="Ej: Programador"
                        class="w-full rounded-md border-slate-300 focus:ring-accent focus:border-accent text-sm">
                </div>

                <div>
                    <label for="publico" class="block text-xs font-medium text-slate-500 mb-1">Sector</label>
                    <select id="publico" name="publico" class="w-full rounded-md border-slate-300 focus:ring-accent focus:border-accent text-sm">
                        <option value="">Público y privado</option>
                        <option value="true">Público</option>
                        <option value="false">Privado</option>
                    </select>
                </div>

                <div>
                    <label for="salario_visible" class="block text-xs font-medium text-slate-500 mb-1">Salario</label>
                    <select id="salario_visible" name="salario_visible" class="w-full rounded-md border-slate-300 focus:ring-accent focus:border-accent text-sm">
                        <option value="">Indistinto</option>
                        <option value="true">Con salario informado</option>
                        <option value="false">Sin salario informado</option>
                    </select>
                </div>

                <div>
                    <label for="modalidad" class="block text-xs font-medium text-slate-500 mb-1">Modalidad</label>
                    <select id="modalidad" name="modalidad" class="w-full rounded-md border-slate-300 focus:ring-accent focus:border-accent text-sm">
                        <option value="">Indistinto</option>
                        <option value="Remoto">Remoto</option>
                        <option value="Híbrido">Híbrido</option>
                        <option value="Presencial">Presencial</option>
                    </select>
                </div>

                <div class="sm:col-span-2 lg:col-span-5 flex flex-wrap items-end gap-3">
                    <div class="flex-1 min-w-[10rem]">
                        <label for="departamento" class="block text-xs font-medium text-slate-500 mb-1">Departamento</label>
                        <select id="departamento" name="departamento" class="w-full rounded-md border-slate-300 focus:ring-accent focus:border-accent text-sm">
                            <option value="">Todo el país</option>
                            @foreach (['Artigas','Canelones','Cerro Largo','Colonia','Durazno','Flores','Florida','Lavalleja','Maldonado','Montevideo','Paysandú','Río Negro','Rivera','Rocha','Salto','San José','Soriano','Tacuarembó','Treinta y Tres'] as $depto)
                                <option value="{{ $depto }}">{{ $depto }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="bg-primary text-white text-sm font-semibold px-5 py-2 rounded-md hover:bg-primary/90 transition">
                        Buscar
                    </button>
                    <button type="button" id="btn-limpiar" class="text-sm text-slate-500 underline hover:text-primary transition">
                        Limpiar filtros
                    </button>
                </div>
            </form>
        </details>

        <p id="resumen-resultados" class="text-sm text-slate-500 mb-4"></p>

        <div id="estado-carga" hidden class="space-y-3" aria-live="polite">
            @for ($i = 0; $i < 4; $i++)
                <div class="animate-pulse bg-white border border-slate-200 rounded-lg p-4 h-28"></div>
            @endfor
        </div>

        <div id="estado-vacio" hidden class="text-center py-16 bg-white border border-slate-200 rounded-lg">
            <p class="text-slate-600 font-medium">No encontramos ofertas con esos filtros.</p>
            <p class="text-slate-400 text-sm mt-1">Probá con otro término o quitá algún filtro.</p>
            <button type="button" id="btn-limpiar-vacio" class="mt-4 text-primary underline text-sm">Limpiar filtros</button>
        </div>

        <div id="estado-error" hidden class="text-center py-16 bg-white border border-red-200 rounded-lg">
            <p class="text-red-600 font-medium">No pudimos cargar las ofertas en este momento.</p>
            <button type="button" id="btn-reintentar" class="mt-4 bg-primary text-white text-sm font-semibold px-5 py-2 rounded-md">Reintentar</button>
        </div>

        <ul id="lista-resultados" class="space-y-3" aria-live="polite"></ul>

        <nav id="paginacion" class="flex items-center justify-center gap-4 mt-8 text-sm" hidden></nav>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/busqueda.js'])
@endpush
