<?php
// BuscoJobs (empresas privadas). Cada página del listado trae las ofertas en un bloque
// JSON llamado __NEXT_DATA__, así que se lee ese bloque en vez de recorrer el HTML.
class BuscoJobs extends Fuente {

    const PAGINAS = 10; // cada página trae unas 15 ofertas, de la más nueva a la más vieja

    public function obtenerOfertas(): array {
        $ofertas = [];
        for ($pagina = 1; $pagina <= self::PAGINAS; $pagina++) {
            $html = $this->descargar("https://www.buscojobs.com.uy/ofertas/$pagina");
            preg_match('#<script id="__NEXT_DATA__"[^>]*>(.*?)</script>#s', $html, $bloque);
            $datos = json_decode($bloque[1] ?? '', true);
            $avisos = $datos['props']['pageProps']['resultadosIniciales']['ofertas'] ?? [];

            foreach ($avisos as $aviso) {
                $ofertas[] = [
                    'titulo'             => $aviso['CargoVacante'],
                    'empresa'            => $aviso['NombreEmpresa'] ?: 'Confidencial',
                    'departamento'       => $this->detectarDepartamento($aviso['Departamento']['Nombre'] ?? ''),
                    'tipo'               => 'Privado',
                    'salario'            => null, // BuscoJobs no publica el salario en el listado
                    'postulacion_online' => true,
                    'primer_empleo'      => $aviso['PrimerEmpleo'] || $aviso['EsPasantia'],
                    'enlace'             => 'https://www.buscojobs.com.uy/oferta-ID-' . $aviso['IdOferta'],
                    'fecha'              => substr($aviso['FechaInicio'] ?? date('Y-m-d'), 0, 10),
                ];
            }
        }
        return $ofertas;
    }
}
