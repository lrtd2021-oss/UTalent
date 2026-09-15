<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo', 'Panel interno') — UTalent</title>
    @vite(['resources/css/app.css'])
    @stack('scripts')
</head>
<body class="min-h-screen flex flex-col bg-slate-100 text-slate-800 antialiased">

    <header class="bg-slate-800 text-slate-100">
        <div class="max-w-5xl mx-auto px-4 py-4 flex items-center justify-between">
            <div>
                <p class="text-xs uppercase tracking-widest text-slate-400">Panel interno</p>
                <p class="font-bold">UTalent — Administración</p>
            </div>
            <nav class="flex gap-4 text-sm">
                <a href="{{ url('/admin/fuentes') }}" class="hover:text-accent transition-colors">Fuentes</a>
                <a href="{{ url('/admin/sinonimos') }}" class="hover:text-accent transition-colors">Sinónimos</a>
            </nav>
        </div>
    </header>

    <main class="flex-1 max-w-5xl mx-auto w-full px-4 py-8">
        @yield('contenido')
    </main>

    <footer class="text-center text-xs text-slate-400 py-6">
        Sin autenticación todavía — solo accesible por URL directa.
    </footer>
</body>
</html>
