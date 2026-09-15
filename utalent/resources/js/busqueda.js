import api from './api';
import { badgeSector, badgeModalidad, textoEmpresa, textoSalario, formatearFecha, escaparHtml } from './utilidades';

const form = document.getElementById('form-filtros');
const listaResultados = document.getElementById('lista-resultados');
const resumen = document.getElementById('resumen-resultados');
const estadoCarga = document.getElementById('estado-carga');
const estadoVacio = document.getElementById('estado-vacio');
const estadoError = document.getElementById('estado-error');
const paginacion = document.getElementById('paginacion');

let paginaActual = 1;
let temporizadorDebounce = null;

function leerFiltrosDeUrl() {
    const params = new URLSearchParams(window.location.search);
    return {
        q: params.get('q') ?? '',
        publico: params.get('publico') ?? '',
        salario_visible: params.get('salario_visible') ?? '',
        modalidad: params.get('modalidad') ?? '',
        departamento: params.get('departamento') ?? '',
        page: parseInt(params.get('page') ?? '1', 10) || 1,
    };
}

function precargarFormulario(filtros) {
    form.q.value = filtros.q;
    form.publico.value = filtros.publico;
    form.salario_visible.value = filtros.salario_visible;
    form.modalidad.value = filtros.modalidad;
    form.departamento.value = filtros.departamento;
}

function filtrosDelFormulario() {
    return {
        q: form.q.value.trim(),
        publico: form.publico.value,
        salario_visible: form.salario_visible.value,
        modalidad: form.modalidad.value,
        departamento: form.departamento.value,
    };
}

function actualizarUrl(filtros, pagina) {
    const params = new URLSearchParams();
    Object.entries(filtros).forEach(([clave, valor]) => {
        if (valor) params.set(clave, valor);
    });
    if (pagina > 1) params.set('page', pagina);

    const query = params.toString();
    history.pushState(null, '', query ? `?${query}` : window.location.pathname);
}

function mostrarEstado(nombre) {
    estadoCarga.hidden = nombre !== 'carga';
    estadoVacio.hidden = nombre !== 'vacio';
    estadoError.hidden = nombre !== 'error';
    listaResultados.hidden = nombre !== 'resultados';
    paginacion.hidden = nombre !== 'resultados';
}

function tarjetaOferta(oferta) {
    const li = document.createElement('li');
    li.className = 'bg-white border border-slate-200 rounded-lg p-4 hover:border-accent transition-colors';

    const fecha = formatearFecha(oferta.fecha_publicacion);
    const tecnologias = Array.isArray(oferta.tecnologias) && oferta.tecnologias.length
        ? `<div class="mt-2 flex flex-wrap gap-1">${oferta.tecnologias.map((t) => `<span class="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded">${escaparHtml(t)}</span>`).join('')}</div>`
        : '';
    const seniority = oferta.seniority
        ? `<span class="text-xs bg-primary/10 text-primary px-2 py-0.5 rounded-full font-medium">${escaparHtml(oferta.seniority)}</span>`
        : '';

    li.innerHTML = `
        <a href="/ofertas/${oferta.id}" class="block">
            <div class="flex flex-wrap items-center gap-2 mb-1">
                ${badgeSector(oferta.es_publico)}
                ${badgeModalidad(oferta.modalidad)}
                ${seniority}
            </div>
            <h3 class="font-bold text-slate-900">${escaparHtml(oferta.titulo)}</h3>
            <p class="text-sm text-slate-500 mt-0.5">
                ${textoEmpresa(oferta)} · ${escaparHtml(oferta.departamento ?? 'Uruguay')}${fecha ? ` · ${fecha}` : ''}
            </p>
            <p class="text-sm text-slate-600 mt-1">${textoSalario(oferta)}</p>
            ${tecnologias}
            <p class="text-xs text-slate-400 mt-2">Fuente: ${escaparHtml(oferta.fuente?.nombre_visible ?? '—')}</p>
        </a>
    `;

    return li;
}

function renderizarPaginacion(datos) {
    paginacion.innerHTML = '';
    if (datos.last_page <= 1) return;

    const anterior = document.createElement('button');
    anterior.textContent = '← Anterior';
    anterior.disabled = datos.current_page <= 1;
    anterior.className = 'px-3 py-1.5 rounded-md border border-slate-300 disabled:opacity-40 disabled:cursor-not-allowed hover:border-accent';
    anterior.addEventListener('click', () => buscar(datos.current_page - 1));

    const info = document.createElement('span');
    info.className = 'text-slate-500';
    info.textContent = `Página ${datos.current_page} de ${datos.last_page}`;

    const siguiente = document.createElement('button');
    siguiente.textContent = 'Siguiente →';
    siguiente.disabled = datos.current_page >= datos.last_page;
    siguiente.className = 'px-3 py-1.5 rounded-md border border-slate-300 disabled:opacity-40 disabled:cursor-not-allowed hover:border-accent';
    siguiente.addEventListener('click', () => buscar(datos.current_page + 1));

    paginacion.append(anterior, info, siguiente);
}

function buscar(pagina = 1) {
    const filtros = filtrosDelFormulario();
    paginaActual = pagina;
    actualizarUrl(filtros, pagina);
    mostrarEstado('carga');

    api.buscarOfertas(filtros, pagina)
        .then((datos) => {
            listaResultados.innerHTML = '';

            if (datos.data.length === 0) {
                resumen.textContent = '';
                mostrarEstado('vacio');
                return;
            }

            resumen.textContent = `${datos.total} resultado${datos.total === 1 ? '' : 's'} encontrado${datos.total === 1 ? '' : 's'}`;
            datos.data.forEach((oferta) => listaResultados.appendChild(tarjetaOferta(oferta)));
            renderizarPaginacion(datos);
            mostrarEstado('resultados');
            listaResultados.scrollIntoView({ behavior: 'smooth', block: 'start' });
        })
        .catch(() => {
            resumen.textContent = '';
            mostrarEstado('error');
        });
}

form.addEventListener('submit', (evento) => {
    evento.preventDefault();
    buscar(1);
});

form.querySelectorAll('select').forEach((select) => {
    select.addEventListener('change', () => buscar(1));
});

form.q.addEventListener('input', () => {
    clearTimeout(temporizadorDebounce);
    temporizadorDebounce = setTimeout(() => buscar(1), 400);
});

function limpiarFiltros() {
    form.reset();
    buscar(1);
}

document.getElementById('btn-limpiar').addEventListener('click', limpiarFiltros);
document.getElementById('btn-limpiar-vacio').addEventListener('click', limpiarFiltros);
document.getElementById('btn-reintentar').addEventListener('click', () => buscar(paginaActual));

const filtrosIniciales = leerFiltrosDeUrl();
precargarFormulario(filtrosIniciales);
buscar(filtrosIniciales.page);
