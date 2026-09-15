export function escaparHtml(texto) {
    const div = document.createElement('div');
    div.textContent = texto ?? '';
    return div.innerHTML;
}

export function formatearFecha(fechaIso) {
    if (!fechaIso) return null;
    // La API devuelve el date cast de Eloquent como datetime ISO completo
    // (ej: "2026-08-27T00:00:00.000000Z"), no solo "AAAA-MM-DD" - Date lo
    // interpreta bien directo, sin concatenarle nada.
    return new Date(fechaIso).toLocaleDateString('es-UY', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

export function badgeSector(esPublico) {
    return esPublico
        ? '<span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium bg-sky-100 text-sky-700">Público</span>'
        : '<span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium bg-violet-100 text-violet-700">Privado</span>';
}

export function badgeModalidad(modalidad) {
    if (!modalidad) return '';
    const colores = {
        Remoto: 'bg-emerald-100 text-emerald-700',
        Híbrido: 'bg-amber-100 text-amber-700',
        Presencial: 'bg-slate-200 text-slate-700',
    };
    const clase = colores[modalidad] ?? 'bg-slate-200 text-slate-700';
    return `<span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium ${clase}">${escaparHtml(modalidad)}</span>`;
}

export function badgeAntigua(esAntigua) {
    return esAntigua
        ? '<span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Oferta antigua</span>'
        : '';
}

export function textoEmpresa(oferta) {
    return oferta.empresa ? escaparHtml(oferta.empresa) : '<span class="italic text-slate-400">Empresa confidencial</span>';
}

export function textoSalario(oferta) {
    return oferta.salario_visible && oferta.salario_texto
        ? escaparHtml(oferta.salario_texto)
        : '<span class="text-slate-400">Salario no informado</span>';
}
