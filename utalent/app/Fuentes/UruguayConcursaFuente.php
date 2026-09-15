<?php

namespace App\Fuentes;

use Illuminate\Support\Facades\Http;

/**
 * Fuente de empleo publico: Uruguay Concursa (uruguayconcursa.gub.uy).
 *
 * El sitio no publica una API documentada, pero su propio buscador consume
 * un endpoint JSON interno (api-backend/llamados/find). Toda referencia a
 * los nombres de campo de ese JSON, la URL del endpoint, la construccion de
 * la URL publica de una oferta y la interpretacion de sus datos vive
 * exclusivamente aca: el resto de UTalent solo ve OfertaDTO.
 */
class UruguayConcursaFuente implements FuenteEmpleoInterface
{
    private const ENDPOINT = 'https://uruguayconcursa.gub.uy/api-backend/llamados/find';

    private const ESTADOS_VIGENTES = ['Abierto', 'Proximo', 'En Curso'];

    private const DEPARTAMENTOS_URUGUAY = [
        'Artigas', 'Canelones', 'Cerro Largo', 'Colonia', 'Durazno', 'Flores',
        'Florida', 'Lavalleja', 'Maldonado', 'Montevideo', 'Paysandú',
        'Río Negro', 'Rivera', 'Rocha', 'Salto', 'San José', 'Soriano',
        'Tacuarembó', 'Treinta y Tres',
    ];

    public function nombre(): string
    {
        return 'uruguay_concursa';
    }

    public function nombreVisible(): string
    {
        return 'Uruguay Concursa';
    }

    public function tipo(): string
    {
        return 'publico';
    }

    /** @return OfertaDTO[] */
    public function buscar(string $termino): array
    {
        $anioActual = (int) now()->format('Y');

        $respuesta = Http::timeout(10)
            ->retry(2, 200)
            ->post(self::ENDPOINT, [
                'llamadosFiltros' => [
                    'PaginaActual' => 1,
                    'CntPorPagina' => 20,
                    'Descripcion' => $termino,
                    'ListaLlaEstWeb' => self::ESTADOS_VIGENTES,
                    'ListaAnios' => [$anioActual - 1, $anioActual, $anioActual + 1],
                ],
            ])
            ->throw();

        return array_map(
            $this->aOfertaDTO(...),
            $respuesta->json('ListaLlamados', [])
        );
    }

    private function aOfertaDTO(array $llamado): OfertaDTO
    {
        // El endpoint de busqueda a veces devuelve "" en vez de omitir el
        // campo cuando el organismo no cargo ese dato: se normaliza a null
        // para que el resto del sistema no tenga que distinguir los dos casos.
        $salarioTexto = $this->nuloSiVacio($llamado['LlaRet'] ?? null);

        return new OfertaDTO(
            externalId: (string) $llamado['LlaId'],
            titulo: ($llamado['CarNom'] ?? null) ?: ($llamado['LlaTit'] ?? 'Sin título'),
            empresa: $this->extraerEmpresa($llamado),
            departamento: $this->extraerDepartamento($llamado['LlaLugDes'] ?? ''),
            esPublico: true,
            salarioVisible: filled($salarioTexto),
            salarioTexto: $salarioTexto,
            modalidad: 'Postulación online (portal Uruguay Concursa)',
            url: 'https://uruguayconcursa.gub.uy/llamado/'.$llamado['LlaId'],
            fechaPublicacion: $this->nuloSiVacio($llamado['LlaFchApeIns'] ?? null),
            fechaCierre: $this->nuloSiVacio($llamado['LlaFchCieIns'] ?? null),
            descripcionCruda: $this->nuloSiVacio($llamado['LlaReqExc'] ?? null),
        );
    }

    private function nuloSiVacio(?string $valor): ?string
    {
        return filled($valor) ? $valor : null;
    }

    private function extraerEmpresa(array $llamado): ?string
    {
        $inciso = trim($llamado['Inciso'] ?? '');
        $unidadEjecutora = trim($llamado['UnidadEjecutora'] ?? '');

        if ($unidadEjecutora !== '' && $unidadEjecutora !== $inciso) {
            return "{$inciso} — {$unidadEjecutora}";
        }

        return $inciso !== '' ? $inciso : null;
    }

    /**
     * LlaLugDes es texto libre (ej: "Departamento de Montevideo." o listas de
     * localidades de varios departamentos). Se busca el primer nombre de
     * departamento uruguayo mencionado; si no aparece ninguno, se devuelve
     * el texto tal cual para no perder la informacion.
     */
    private function extraerDepartamento(string $lugarDesempeno): ?string
    {
        if ($lugarDesempeno === '') {
            return null;
        }

        foreach (self::DEPARTAMENTOS_URUGUAY as $departamento) {
            if (mb_stripos($lugarDesempeno, $departamento) !== false) {
                return $departamento;
            }
        }

        return trim($lugarDesempeno);
    }
}
