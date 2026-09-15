import api from './api';
import { escaparHtml } from './utilidades';

const form = document.getElementById('form-sinonimo');
const btnGuardar = document.getElementById('btn-guardar-sinonimo');
const btnCancelar = document.getElementById('btn-cancelar-sinonimo');
const mensaje = document.getElementById('mensaje-sinonimo');
const tabla = document.getElementById('tabla-sinonimos');

function mostrarMensaje(texto, esError = false) {
    mensaje.textContent = texto;
    mensaje.className = `text-sm mb-4 ${esError ? 'text-red-600' : 'text-emerald-600'}`;
}

function entrarEnModoEdicion(sinonimo) {
    form.id.value = sinonimo.id;
    form.termino.value = sinonimo.termino;
    form.grupo.value = sinonimo.grupo;
    btnGuardar.textContent = 'Guardar cambios';
    btnCancelar.hidden = false;
}

function salirDeModoEdicion() {
    form.reset();
    form.id.value = '';
    btnGuardar.textContent = 'Crear sinónimo';
    btnCancelar.hidden = true;
}

function filaSinonimo(sinonimo) {
    const tr = document.createElement('tr');
    tr.className = 'border-t border-slate-100';
    tr.innerHTML = `
        <td class="px-4 py-2 font-mono text-xs">${escaparHtml(sinonimo.termino)}</td>
        <td class="px-4 py-2">${escaparHtml(sinonimo.grupo)}</td>
        <td class="px-4 py-2 space-x-3">
            <button type="button" class="text-primary underline text-xs" data-accion="editar">Editar</button>
            <button type="button" class="text-red-600 underline text-xs" data-accion="eliminar">Eliminar</button>
        </td>
    `;

    tr.querySelector('[data-accion="editar"]').addEventListener('click', () => entrarEnModoEdicion(sinonimo));
    tr.querySelector('[data-accion="eliminar"]').addEventListener('click', () => eliminar(sinonimo));

    return tr;
}

function eliminar(sinonimo) {
    if (!confirm(`¿Eliminar el sinónimo "${sinonimo.termino}"?`)) return;

    api.eliminarSinonimo(sinonimo.id)
        .then(() => {
            mostrarMensaje('Sinónimo eliminado.');
            cargarSinonimos();
        })
        .catch(() => mostrarMensaje('No se pudo eliminar el sinónimo.', true));
}

function cargarSinonimos() {
    api.listarSinonimos().then((sinonimos) => {
        tabla.innerHTML = '';
        sinonimos.forEach((sinonimo) => tabla.appendChild(filaSinonimo(sinonimo)));
    });
}

form.addEventListener('submit', (evento) => {
    evento.preventDefault();

    const id = form.id.value;
    const datos = { termino: form.termino.value, grupo: form.grupo.value };

    const peticion = id ? api.actualizarSinonimo(id, datos) : api.crearSinonimo(datos);

    peticion
        .then(() => {
            mostrarMensaje(id ? 'Sinónimo actualizado.' : 'Sinónimo creado.');
            salirDeModoEdicion();
            cargarSinonimos();
        })
        .catch((error) => {
            const primerError = error.errores ? Object.values(error.errores)[0][0] : error.mensaje;
            mostrarMensaje(primerError, true);
        });
});

btnCancelar.addEventListener('click', salirDeModoEdicion);

cargarSinonimos();
