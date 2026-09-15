<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo', 'UTalent') — Empleo en tecnología en Uruguay</title>
    @vite(['resources/css/app.css'])
    @stack('scripts')
</head>
<body class="min-h-screen flex flex-col bg-slate-50 text-slate-800 antialiased">

    <header class="bg-primary text-white">
        <nav class="max-w-5xl mx-auto px-4 py-4 flex items-center justify-between">
            <a href="{{ url('/') }}" class="text-xl font-bold tracking-tight">
                UTalent<span class="text-accent">.</span>
            </a>

            <button id="menu-toggle" class="sm:hidden" aria-label="Abrir menú" aria-expanded="false">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <div id="menu" class="hidden sm:flex gap-6 text-sm font-medium">
                <a href="{{ url('/') }}" class="hover:text-accent transition-colors">Inicio</a>
                <a href="{{ url('/ofertas') }}" class="hover:text-accent transition-colors">Buscar ofertas</a>
            </div>
        </nav>

        <div id="menu-mobile" class="hidden sm:hidden px-4 pb-4 flex flex-col gap-3 text-sm font-medium">
            <a href="{{ url('/') }}" class="hover:text-accent transition-colors">Inicio</a>
            <a href="{{ url('/ofertas') }}" class="hover:text-accent transition-colors">Buscar ofertas</a>
        </div>
    </header>

    <main class="flex-1">
        @yield('contenido')
    </main>

    <footer class="bg-white border-t border-slate-200 py-8 text-center text-sm text-slate-500">
        <p>UTalent — Proyecto académico, Esc. Técnica Rocha (UTU). Datos agregados desde fuentes públicas y privadas.</p>
    </footer>

    <script>
        const menuToggle = document.getElementById('menu-toggle');
        const menuMobile = document.getElementById('menu-mobile');

        menuToggle?.addEventListener('click', () => {
            const oculto = menuMobile.classList.toggle('hidden');
            menuToggle.setAttribute('aria-expanded', oculto ? 'false' : 'true');
        });
    </script>
</body>
</html>
