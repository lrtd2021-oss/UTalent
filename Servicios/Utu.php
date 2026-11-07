<?php
// UTU / DGETP (llamados de la propia UTU). Su web está hecha con WordPress, que trae
// una API JSON oficial: /wp-json/wp/v2/posts devuelve las publicaciones sin leer HTML.
class Utu extends Fuente {

    public function obtenerOfertas(): array {
        $desde = date('Y-m-d\T00:00:00', strtotime('-60 days')); // solo publicaciones recientes
        $json = $this->descargar('https://www.utu.edu.uy/wp-json/wp/v2/posts?search=llamado&per_page=100'
            . "&after=$desde&_fields=date,link,title");

        $ofertas = [];
        foreach (json_decode($json, true) as $publicacion) {
            $titulo = html_entity_decode($publicacion['title']['rendered'], ENT_QUOTES, 'UTF-8');

            // La web también publica las etapas siguientes de cada llamado (designaciones, nóminas,
            // resoluciones). Solo sirven los anuncios que empiezan con "Llamado".
            if (!preg_match('/^Llamado/i', $titulo) || preg_match('/designaci|n[oó]mina|considerado|resultado|pr[oó]rroga|fallo/i', $titulo)) {
                continue;
            }
            $ofertas[] = [
                'titulo'             => mb_strimwidth($titulo, 0, 255, '…'),
                'empresa'            => 'UTU (DGETP)',
                'departamento'       => preg_match('/nacional/i', $titulo) ? 'Varios departamentos' : $this->detectarDepartamento($titulo),
                'tipo'               => 'Público',
                'salario'            => null,
                'postulacion_online' => false, // cada llamado explica su forma de inscripción en las bases
                'primer_empleo'      => false,
                'enlace'             => $publicacion['link'],
                'fecha'              => substr($publicacion['date'], 0, 10),
            ];
        }
        return $ofertas;
    }
}
