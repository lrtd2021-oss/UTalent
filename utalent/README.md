# UTalent

Buscador de oportunidades laborales en Tecnologías de la Información para estudiantes y egresados de UTU. Agrega ofertas de Uruguay Concursa (público) y BuscoJobs Uruguay (privado) en un único buscador con filtros por sector, salario visible, modalidad y departamento.

Cuarto Práctico de Programación Full-Stack — Esc. Técnica Rocha (UTU), 2026. Documentación completa (arquitectura, diagrama de clases, decisiones técnicas, uso de IA) en [`../documentación/UTalent_Documentacion.pdf`](../documentación/UTalent_Documentacion.pdf).

## Requisitos

- PHP 8.2+ con extensión `pdo_sqlite`
- Composer
- Node.js + npm

No hace falta MySQL, PostgreSQL, Redis ni Memcached: la base de datos es un único archivo SQLite y la cola usa el driver `database`.

## Instalación

```bash
composer setup
```

Este comando instala dependencias PHP, copia `.env.example` a `.env`, genera `APP_KEY`, corre las migraciones y compila el frontend (`npm install` + `npm run build`).

## Generar datos reales

Un proyecto recién clonado no tiene ofertas: hay que correr al menos un barrido contra las fuentes reales. La cola usa el driver `database` (no `sync`), así que un Job despachado necesita un worker corriendo para ejecutarse:

```bash
php artisan buscador:actualizar-todo   # despacha el barrido completo
php artisan queue:work --once          # lo procesa
```

## Levantar el proyecto

```bash
composer dev
```

Levanta en paralelo el servidor (`php artisan serve`), un worker de cola (`queue:listen`), el visor de logs (`pail`) y Vite en modo watch.

## Tests

```bash
php artisan test
```

## Variables de entorno relevantes

| Variable | Rol |
|---|---|
| `DB_CONNECTION=sqlite` | Base de datos en un único archivo (`database/database.sqlite`) |
| `QUEUE_CONNECTION=database` | Los Jobs se procesan por un worker, no en el mismo request |
| `OPENROUTER_API_KEY`, `OPENROUTER_MODEL` | Opcionales — sin configurar, el sistema guarda ofertas normalmente, solo sin enriquecer con seniority/tecnologías |

Ninguna de estas variables, ni `.env`, se sube al repositorio.

## Arquitectura (resumen)

MVC (Laravel) + Strategy (`FuenteEmpleoInterface`, una implementación por fuente) + Repository (persistencia aislada detrás de interfaces) + un Service orquestador (`BuscadorService`). El detalle completo, con el diagrama de clases actualizado contra el código real, está en la documentación del proyecto (enlace arriba).
