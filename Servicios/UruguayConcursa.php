<?php
// Uruguay Concursa (llamados públicos). Su web usa una API que responde en JSON,
// así que no hace falta leer HTML: se le piden directamente los llamados abiertos.
class UruguayConcursa extends Fuente {

    const POR_PAGINA = 100; // la API es lenta (~0,4 s por llamado), así que se pide de a 100

    public function obtenerOfertas(): array {
        $llamados = [];
        $pagina = 1;
        do {
            $filtros = ['llamadosFiltros' => ['PaginaActual' => $pagina, 'CntPorPagina' => self::POR_PAGINA, 'ListaLlaEstWeb' => ['Abierto']]];
            $json = $this->descargar('https://www.uruguayconcursa.gub.uy/api-backend/llamados/find', $filtros);
            $paginaActual = json_decode($json, true)['ListaLlamados'] ?? [];
            $llamados = array_merge($llamados, $paginaActual);
            $pagina++;
        } while (count($paginaActual) === self::POR_PAGINA); // una página incompleta es la última

        $ofertas = [];
        foreach ($llamados as $llamado) {
            // El sueldo viene en un texto largo; nos quedamos solo con el monto ("$56.120").
            preg_match('/\$\s?U?\s?[\d.,]+/', $llamado['LlaRet'] ?? '', $monto);
            // Muchos llamados no traen el lugar; el departamento suele aparecer en el organismo
            // ("Intendencia de Rocha"), la unidad o el título.
            $textoLugar = ($llamado['LlaLugDes'] ?? '') . ' ' . ($llamado['Inciso'] ?? '') . ' '
                . ($llamado['UnidadEjecutora'] ?? '') . ' ' . $llamado['LlaTit'];
            $ofertas[] = [
                'titulo'             => $llamado['LlaTit'],
                'empresa'            => $llamado['Inciso'] ?? 'Estado',
                'departamento'       => $this->detectarDepartamento($textoLugar),
                'tipo'               => 'Público',
                'salario'            => $monto[0] ?? null,
                'postulacion_online' => str_contains($llamado['LlaMedPos'] ?? '', 'uruguayconcursa'),
                'primer_empleo'      => false,
                'enlace'             => 'https://www.uruguayconcursa.gub.uy/llamado/' . $llamado['LlaId'],
                'fecha'              => $llamado['LlaFchApeIns'] ?? date('Y-m-d'),
            ];
        }
        return $ofertas;
    }
}
