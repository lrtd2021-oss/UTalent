@extends('layouts.app')

@section('titulo', 'UTalent')

@section('contenido')
    <section class="bg-primary text-white">
        <div class="max-w-3xl mx-auto px-4 py-16 sm:py-24 text-center">
            <h1 class="text-3xl sm:text-4xl font-bold leading-tight">
                Encontrá tu próxima oportunidad en tecnología
            </h1>
            <p class="mt-4 text-slate-300 text-base sm:text-lg">
                Buscador de empleo en informática para estudiantes y egresados de UTU en Uruguay.
                Reunimos ofertas públicas y privadas en un solo lugar.
            </p>

            <form action="{{ url('/ofertas') }}" method="GET" class="mt-8 flex flex-col sm:flex-row gap-2 max-w-xl mx-auto">
                <label for="q" class="sr-only">Puesto o tecnología</label>
                <input
                    type="text" id="q" name="q" placeholder="Ej: Programador, Soporte Técnico..."
                    class="flex-1 rounded-md px-4 py-3 text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-accent"
                >
                <button type="submit" class="bg-accent text-primary font-semibold px-6 py-3 rounded-md hover:brightness-110 transition">
                    Buscar
                </button>
            </form>
        </div>
    </section>

    <section class="max-w-5xl mx-auto px-4 py-12 grid gap-6 sm:grid-cols-3">
        <div class="bg-white rounded-lg border border-slate-200 p-6">
            <p class="text-accent font-bold text-2xl mb-1">2 fuentes</p>
            <p class="text-slate-600 text-sm">Empleo público (Uruguay Concursa) y privado (BuscoJobs) en un solo buscador.</p>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-6">
            <p class="text-accent font-bold text-2xl mb-1">Filtros que importan</p>
            <p class="text-slate-600 text-sm">Público o privado, salario visible, modalidad y departamento — sin ruido.</p>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-6">
            <p class="text-accent font-bold text-2xl mb-1">Pensado para IT</p>
            <p class="text-slate-600 text-sm">Enfocado en tecnología: desarrollo, soporte, redes, bases de datos y más.</p>
        </div>
    </section>

    <section class="max-w-5xl mx-auto px-4 pb-16 text-center">
        <a href="{{ url('/ofertas') }}" class="inline-block text-primary font-semibold underline decoration-accent decoration-2 underline-offset-4 hover:text-accent transition-colors">
            Ver todas las oportunidades disponibles →
        </a>
    </section>
@endsection
