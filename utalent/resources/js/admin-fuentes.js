import api from './api';
import { escaparHtml } from './utilidades';

const form = document.getElementById('form-fuente');
const campoNombre = document.getElementById('campo-nombre');
const btnGuardar = document.getElementById('btn-guardar-fuente');
const btnCancelar = document.getElementById('btn-cancelar-fuente');
const mensaje = document.getElementById('mensaje-fuente');
const tabla = document.getElementById('tabla-fuentes');

function mostrarMensaje(texto, esError = false) {
    mensaje.textContent = texto;
    mensaje.className = `text-sm mb-4 ${esError ? 'text-red-600' : 'text-emerald-600'}`;
}

function entrarEnModoEdicion(fuente) {
    form.id.value = fuente.id;
    form.nombre_visible.value = fuente.nombre_visible;
    form.tipo.value = fuente.tipo;
    form.activa.checked = fuente.activa;
    campoNombre.hidden = true;
    form.nombre.required = false;
    btnGuardar.textContent = 'Guardar cambios';
    btnCancelar.hidden = false;
}

function salirDeModoEdicion() {
    form.reset();
    form.id.value = '';
    campoNombre.hidden = false;
    form.nombre.required = true;
    btnGuardar.textContent = 'Crear fuente';
    btnCancelar.hidden = true;
}

function filaFuente(fuente) {
    const tr = document.createElement('tr');
    tr.className = 'border-t border-slate-100';
    tr.innerHTML = `
        <td class="px-4 py-2 font-mono text-xs">${escaparHtml(fuente.nombre)}</td>
        <td class="px-4 py-2">${escaparHtml(fuente.nombre_visible)}</td>
        <td class="px-4 py-2 capitalize">${escaparHtml(fuente.tipo)}</td>
        <td class="px-4 py-2">
            <span class="px-2 py-0.5 rounded-full text-xs font-medium ${fuente.activa ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-500'}">
                ${fuente.activa ? 'Activa' : 'Inactiva'}
            </span>
        </td>
        <td class="px-4 py-2 space-x-3">
            <button type="button" class="text-primary underline text-xs" data-accion="editar">Editar</button>
            <button type="button" class="text-xs underline ${fuente.activa ? 'text-red-600' : 'text-emerald-600'}" data-accion="alternar">
                ${fuente.activa ? 'Desactivar' : 'Activar'}
            </button>
        </td>
    `;

    tr.querySelector('[data-accion="editar"]').addEventListener('click', () => entrarEnModoEdicion(fuente));
    tr.querySelector('[data-accion="alternar"]').addEventListener('click', () => alternarActiva(fuente));

    return tr;
}

function alternarActiva(fuente) {
    const accion = fuente.activa
        ? api.desactivarFuente(fuente.id)
        : api.actualizarFuente(fuente.id, { activa: true });

    accion.then(() => {
        mostrarMensaje(fuente.activa ? 'Fuente desactivada.' : 'Fuente activada.');
        cargarFuentes();
    }).catch(() => mostrarMensaje('No se pudo actualizar la fuente.', true));
}

function cargarFuentes() {
    api.listarFuentes().then((fuentes) => {
        tabla.innerHTML = '';
        fuentes.forEach((fuente) => tabla.appendChild(filaFuente(fuente)));
    });
}

form.addEventListener('submit', (evento) => {
    evento.preventDefault();

    const id = form.id.value;
    const datos = {
        nombre_visible: form.nombre_visible.value,
        tipo: form.tipo.value,
        activa: form.activa.checked,
    };

    const peticion = id
        ? api.actualizarFuente(id, datos)
        : api.crearFuente({ ...datos, nombre: form.nombre.value });

    peticion
        .then(() => {
            mostrarMensaje(id ? 'Fuente actualizada.' : 'Fuente creada.');
            salirDeModoEdicion();
            cargarFuentes();
        })
        .catch((error) => {
            const primerError = error.errores ? Object.values(error.errores)[0][0] : error.mensaje;
            mostrarMensaje(primerError, true);
        });
});

btnCancelar.addEventListener('click', salirDeModoEdicion);

cargarFuentes();
