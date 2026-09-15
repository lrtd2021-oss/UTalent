import api from './api';
import { badgeSector, badgeModalidad, badgeAntigua, textoEmpresa, textoSalario, formatearFecha, escaparHtml } from './utilidades';

const contenedor = document.getElementById('detalle');
const id = contenedor.dataset.ofertaId;

const estadoCarga = document.getElementById('estado-carga');
const estadoError = document.getElementById('estado-error');
const estadoNoEncontrada = document.getElementById('estado-no-encontrada');
const contenidoOferta = document.getElementById('contenido-oferta');

function mostrarEstado(nombre) {
    estadoCarga.hidden = nombre !== 'carga';
    estadoError.hidden = nombre !== 'error';
    estadoNoEncontrada.hidden = nombre !== 'no-encontrada';
    contenidoOferta.hidden = nombre !== 'contenido';
}

function pintarOferta(oferta) {
    document.getElementById('aviso-cerrada').hidden = oferta.estado === 'activa';

    const seniority = oferta.seniority
        ? `<span class="text-xs bg-primary/10 text-primary px-2 py-0.5 rounded-full font-medium">${escaparHtml(oferta.seniority)}</span>`
        : '';
    document.getElementById('badges').innerHTML = `${badgeSector(oferta.es_publico)}${badgeModalidad(oferta.modalidad)}${seniority}${badgeAntigua(oferta.es_antigua)}`;

    document.getElementById('titulo').textContent = oferta.titulo;
    document.getElementById('empresa-departamento').innerHTML = `${textoEmpresa(oferta)} · ${escaparHtml(oferta.departamento ?? 'Uruguay')}`;

    const tecnologias = Array.isArray(oferta.tecnologias) ? oferta.tecnologias : [];
    document.getElementById('tecnologias').innerHTML = tecnologias
        .map((t) => `<span class="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded">${escaparHtml(t)}</span>`)
        .join('');

    document.getElementById('descripcion').textContent = oferta.descripcion_cruda || 'La fuente no publicó una descripción detallada.';
    document.getElementById('salario').innerHTML = textoSalario(oferta);
    document.getElementById('fecha-publicacion').textContent = formatearFecha(oferta.fecha_publicacion) ?? 'No informada';
    document.getElementById('fuente').textContent = oferta.fuente?.nombre_visible ?? '—';

    const cta = document.getElementById('cta-original');
    cta.href = oferta.url;
}

function cargar() {
    mostrarEstado('carga');

    api.obtenerOferta(id)
        .then((oferta) => {
            pintarOferta(oferta);
            mostrarEstado('contenido');
        })
        .catch((error) => {
            mostrarEstado(error.status === 404 ? 'no-encontrada' : 'error');
        });
}

document.getElementById('btn-reintentar').addEventListener('click', cargar);

cargar();
