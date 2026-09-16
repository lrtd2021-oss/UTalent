<p align="center">
  <h1 align="center">UTalent</h1>
  <p align="center">Buscador de oportunidades laborales en Tecnologías de la Información para estudiantes y egresados de UTU</p>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white" alt="Laravel 12">
  <img src="https://img.shields.io/badge/Tailwind_CSS-4-06B6D4?logo=tailwindcss&logoColor=white" alt="Tailwind CSS 4">
  <img src="https://img.shields.io/badge/SQLite-embebido-003B57?logo=sqlite&logoColor=white" alt="SQLite">
  <img src="https://img.shields.io/badge/License-MIT-informational" alt="MIT License">
</p>

UTalent agrega en un único lugar ofertas de empleo en tecnología publicadas en Uruguay: el portal estatal **Uruguay Concursa** (sector público) y el portal privado **BuscoJobs Uruguay**. El usuario busca por título de puesto, filtra por sector, salario visible, modalidad y departamento, y ve resultados combinados de ambas fuentes sin tener que visitarlas por separado.

Proyecto académico — Cuarto Práctico de Programación Full-Stack, Esc. Técnica Rocha (UTU), 2026.

---

## Índice

- [Sobre el proyecto](#sobre-el-proyecto)
- [Funcionalidades](#funcionalidades)
- [Fuentes de empleo](#fuentes-de-empleo)
- [Stack tecnológico](#stack-tecnológico)
- [Arquitectura](#arquitectura)
- [Instalación](#instalación)
- [Generar datos reales](#generar-datos-reales)
- [Levantar el proyecto](#levantar-el-proyecto)
- [Variables de entorno](#variables-de-entorno)
- [Tests](#tests)
- [API](#api)
- [Uso de Inteligencia Artificial](#uso-de-inteligencia-artificial)
- [Limitaciones conocidas](#limitaciones-conocidas)
- [Documentación completa](#documentación-completa)
- [Licencia](#licencia)

---

## Sobre el proyecto

**Problema que resuelve:** un estudiante o egresado de UTU que busca su primer empleo en informática hoy tiene que revisar por separado el portal estatal de concursos públicos y varios portales privados de empleo, cada uno con su propia forma de buscar y filtrar. UTalent agrega ambos tipos de fuente, normaliza los datos a un formato común y permite buscar y filtrar una sola vez.

**Alcance:** según la consigna del práctico, el sistema **no** incluye registro de usuarios ni postulación desde la propia aplicación — muestra y filtra ofertas, y deriva al sitio original para postularse ("Ver publicación original"). Dentro de ese alcance, el proyecto va más allá del MVP mínimo: dos fuentes reales con mecanismos de datos distintos, actualización periódica en segundo plano con cierre correcto de ofertas vencidas, enriquecimiento opcional con IA, y un panel de administración para el propio motor de búsqueda.

## Funcionalidades

- 🔍 Búsqueda por título de puesto con expansión automática por sinónimos (buscar "programador" también encuentra "developer").
- 🎚️ Filtros combinables: sector (público/privado), salario visible, modalidad laboral (Remoto/Híbrido/Presencial) y departamento.
- 📄 Detalle de cada oferta, incluidas las ya cerradas (se muestran igual, marcadas como no disponibles).
- 🏷️ Señal visual de "oferta antigua" (+180 días) sin que afecte si la oferta sigue activa.
- ⏱️ Actualización automática en segundo plano cada 6 horas, con cierre de ofertas que ya no aparecen en la fuente original.
- 🤖 Enriquecimiento opcional de cada oferta con *seniority* y tecnologías detectadas por IA.
- 🛠️ Panel de administración (`/admin/*`) para dar de alta/baja fuentes y administrar sinónimos de búsqueda.
- 🔌 API REST propia (JSON) que expone toda la búsqueda y el ABM.

## Fuentes de empleo

| Fuente | Tipo | Mecanismo |
|---|---|---|
| [Uruguay Concursa](https://uruguayconcursa.gub.uy) | Público | Endpoint JSON interno del propio buscador del sitio, identificado por inspección de red (no scraping de HTML). |
| [BuscoJobs Uruguay](https://www.buscojobs.com.uy) | Privado | SPA en Next.js: dos peticiones encadenadas (buildId + datos) más una tercera opcional para completar descripciones truncadas. |

Ambas fuentes implementan la misma interfaz (`FuenteEmpleoInterface`, patrón **Strategy**): agregar una fuente nueva no requiere tocar el resto del sistema.

## Stack tecnológico

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.2 + Laravel 12, SQLite, Eloquent, Queue (driver `database`), Scheduler |
| Frontend | Blade (solo estructura) + Tailwind CSS 4 + JavaScript en módulos ES + Axios + Vite |
| IA | [OpenRouter](https://openrouter.ai) (proveedor configurable, sin SDK, solo `Http::`) |

Sin frameworks de componentes (React/Vue/Alpine/Livewire/Inertia) y sin librerías de scraping de terceros: todo el frontend es JavaScript vanilla y todas las fuentes se consultan con el cliente HTTP nativo de Laravel.

## Arquitectura

**MVC** (Laravel, por convención) + **Strategy** (`FuenteEmpleoInterface`, una implementación por fuente) + **Repository** (persistencia aislada detrás de interfaces) + un **Service** orquestador (`BuscadorService`) que nunca conoce el detalle de ninguna fuente externa ni del proveedor de IA.

<p align="center">
  <img src="../documentación/img/diagrama_clases.png" alt="Diagrama de clases de UTalent" width="850">
</p>

<p align="center"><em>Diagrama de clases actualizado contra el código real del repositorio (no un boceto de diseño previo).</em></p>

## Instalación

Requisitos: **PHP 8.2+** (con `pdo_sqlite`), **Composer**, **Node.js + npm**. No hace falta MySQL, PostgreSQL, Redis ni Memcached — la base de datos es un único archivo SQLite y la cola usa el driver `database`.

```bash
composer setup
```

Ese único comando instala las dependencias PHP, copia `.env.example` a `.env`, genera `APP_KEY`, corre las migraciones y compila el frontend (`npm install` + `npm run build`).

## Generar datos reales

Un proyecto recién clonado no tiene ofertas. Como la cola usa el driver `database` (no `sync`), un Job despachado necesita un worker corriendo para ejecutarse:

```bash
php artisan buscador:actualizar-todo   # despacha el barrido completo contra las fuentes reales
php artisan queue:work --once          # lo procesa
```

## Levantar el proyecto

```bash
composer dev
```

Levanta en paralelo el servidor (`php artisan serve`), un worker de cola (`queue:listen`), el visor de logs (`pail`) y Vite en modo watch, todo en una sola terminal.

## Variables de entorno

| Variable | Rol |
|---|---|
| `DB_CONNECTION=sqlite` | Base de datos en un único archivo (`database/database.sqlite`) |
| `QUEUE_CONNECTION=database` | Los Jobs se procesan por un worker, no en el mismo request |
| `DB_QUEUE_RETRY_AFTER=3600` | Debe superar el timeout más largo entre los Jobs (1800s) |
| `OPENROUTER_API_KEY` | Opcional — sin configurar, el sistema guarda ofertas normalmente, solo sin enriquecer con *seniority*/tecnologías |
| `OPENROUTER_MODEL` | Modelo de OpenRouter a usar (parametrizable, no fijado en el código; ver [Uso de Inteligencia Artificial](#uso-de-inteligencia-artificial)) |

Ninguna de estas variables, ni el archivo `.env` real, se sube al repositorio.

## Tests

```bash
php artisan test
```

82 tests automatizados (PHPUnit) cubriendo las dos Strategy de fuentes, el mecanismo de cierre de ofertas, la resiliencia ante fallos de fuentes/IA, la API de búsqueda y ABM, y la idempotencia del guardado.

## API

| Método | Ruta | Descripción |
|---|---|---|
| `GET` | `/api/ofertas` | Búsqueda con filtros y paginación (solo lee datos locales) |
| `GET` | `/api/ofertas/{oferta}` | Detalle de una oferta (activa o cerrada) |
| `GET/POST/PUT/DELETE` | `/api/fuentes` | ABM de fuentes de empleo (baja lógica, no borrado) |
| `GET/POST/PUT/DELETE` | `/api/sinonimos` | ABM de sinónimos de búsqueda |

Toda la API responde JSON (incluidos los errores de validación) y tiene un límite nativo de 60 peticiones/minuto por IP.

## Uso de Inteligencia Artificial

UTalent usa IA en dos sentidos distintos, ambos documentados sin ocultar ni exagerar su alcance:

- **Como parte del producto** — `NormalizadorIA` (vía OpenRouter) extrae *seniority* y tecnologías del texto de cada oferta. El modelo actualmente previsto (`nvidia/nemotron-3-super-120b-a12b:free`) se eligió tras una prueba controlada con 5 llamadas reales sobre ofertas reales de esta misma base, no solo por sus características publicadas. Si el proveedor de IA falla o no hay API key configurada, el sistema sigue guardando ofertas con normalidad, solo sin ese enriquecimiento.
- **Como herramienta de desarrollo** — el proyecto se construyó con asistencia de un asistente de IA (Claude Code), bajo dirección explícita del estudiante en cada fase, con verificación real (llamadas HTTP reales, tests automatizados, pruebas manuales en navegador) antes de dar cada parte por cerrada.

El detalle completo de ambos usos está en la sección 20 de la documentación.

## Limitaciones conocidas

- Solo dos fuentes de empleo (el diseño admite agregar más sin tocar el resto del sistema).
- Sin filtro de antigüedad (la señal "oferta antigua" es solo visual).
- Sin autenticación en `/admin/*` — aceptable para el alcance académico del práctico, no para un entorno expuesto a internet.
- Sin tests automatizados de frontend (JavaScript); esa capa se valida siempre en navegador real.
- Uruguay Concursa no expone modalidad laboral real, por lo que el filtro de modalidad no aplica a esa fuente (por diseño, no por defecto).

Detalle completo en la sección 23 de la documentación.

## Documentación completa

La documentación del proyecto — presentación, arquitectura, flujos, API, IA, frontend, accesibilidad, tests, instalación y limitaciones, 24 secciones en total — está en [`documentación/UTalent_Documentacion.pdf`](../documentación/UTalent_Documentacion.pdf).

## Licencia

[MIT](https://opensource.org/licenses/MIT).

---

<p align="center"><sub>Lucas Techera (C.I. 4.999.390-1) — 3.° BT Tecnologías de la Información, Esc. Técnica Rocha (UTU) — Rocha, 2026</sub></p>
