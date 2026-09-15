import axios from 'axios';

/**
 * Unico punto de contacto del frontend con la API de UTalent. Ninguna
 * pagina arma una URL de /api/* a mano: todas pasan por aca.
 */
const cliente = axios.create({ baseURL: '/api' });

cliente.interceptors.response.use(
    (respuesta) => respuesta,
    (error) => {
        return Promise.reject({
            status: error.response?.status ?? 0,
            mensaje: error.response?.data?.message ?? 'No pudimos conectarnos con el servidor.',
            errores: error.response?.data?.errors ?? null,
        });
    }
);

/**
 * La API distingue "el filtro no vino" de "vino vacio" (Request::has()),
 * asi que un valor "" no puede mandarse como si el usuario lo hubiera
 * elegido: se descarta antes de armar los params.
 */
function limpiarVacios(objeto) {
    return Object.fromEntries(Object.entries(objeto).filter(([, valor]) => valor !== '' && valor !== null && valor !== undefined));
}

export default {
    buscarOfertas(filtros = {}, pagina = 1) {
        return cliente.get('/ofertas', { params: { ...limpiarVacios(filtros), page: pagina } }).then((r) => r.data);
    },

    obtenerOferta(id) {
        return cliente.get(`/ofertas/${id}`).then((r) => r.data);
    },

    listarFuentes() {
        return cliente.get('/fuentes').then((r) => r.data);
    },

    crearFuente(datos) {
        return cliente.post('/fuentes', datos).then((r) => r.data);
    },

    actualizarFuente(id, datos) {
        return cliente.put(`/fuentes/${id}`, datos).then((r) => r.data);
    },

    desactivarFuente(id) {
        return cliente.delete(`/fuentes/${id}`).then((r) => r.data);
    },

    listarSinonimos() {
        return cliente.get('/sinonimos').then((r) => r.data);
    },

    crearSinonimo(datos) {
        return cliente.post('/sinonimos', datos).then((r) => r.data);
    },

    actualizarSinonimo(id, datos) {
        return cliente.put(`/sinonimos/${id}`, datos).then((r) => r.data);
    },

    eliminarSinonimo(id) {
        return cliente.delete(`/sinonimos/${id}`);
    },
};
