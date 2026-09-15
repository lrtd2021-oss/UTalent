<?php

namespace App\Fuentes;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Fuente de empleo privada: BuscoJobs Uruguay (buscojobs.com.uy).
 *
 * Es una SPA en Next.js: sus resultados de busqueda no vienen en el HTML,
 * los trae un endpoint JSON interno identificado por un "buildId" que
 * cambia con cada despliegue del sitio. Por eso cada busqueda son DOS
 * peticiones: (1) la pagina de busqueda, de donde se extrae el buildId
 * vigente, y (2) el endpoint de datos con ese buildId. Toda esta mecanica,
 * los nombres de campo del JSON, y la construccion de la URL publica de una
 * oferta viven exclusivamente aca: el resto de UTalent solo ve OfertaDTO.
 */
class BuscoJobsFuente implements FuenteEmpleoInterface
{
    private const URL_BASE = 'https://www.buscojobs.com.uy';

    /**
     * Descripcion completa ya resuelta por IdOferta durante esta ejecucion
     * (Fase 9.2): evita pedir el detalle dos veces si la misma oferta
     * aparece bajo mas de un termino semilla en el mismo barrido. Vive
     * solo mientras viva esta instancia - no es una cache persistente.
     *
     * @var array<int|string, ?string>
     */
    private array $cacheDescripciones = [];

    public function nombre(): string
    {
        return 'buscojobs';
    }

    public function nombreVisible(): string
    {
        return 'BuscoJobs Uruguay';
    }

    public function tipo(): string
    {
        return 'privado';
    }

    /** @return OfertaDTO[] */
    public function buscar(string $termino): array
    {
        $slug = $this->slugificar($termino);

        // Si no se consigue un buildId valido, no tiene sentido intentar la
        // segunda peticion (fallaria igual) ni devolver resultados a medias:
        // se lanza la excepcion y BuscadorService ya sabe tratar una fuente
        // que fallo (la deja afuera de esta corrida, sin tumbar las demas).
        $buildId = $this->obtenerBuildId($slug);

        // throw:false porque el propio retry() de Laravel, con mas de un
        // intento, lanza su excepcion generica ANTES de que este metodo
        // pueda revisar $respuesta->failed() - se lo desactiva para que el
        // mensaje de error propio (mas util para el log) sea el que se vea.
        $respuesta = Http::timeout(10)
            ->retry(2, 200, throw: false)
            ->acceptJson()
            ->get(self::URL_BASE.'/_next/data/'.rawurlencode($buildId).'/es-UY/ofertas/'.rawurlencode($slug).'.json', [
                'filtersOrQueryOrPage' => $slug,
            ]);

        if ($respuesta->failed()) {
            throw new RuntimeException("BuscoJobs: la busqueda de ofertas fallo con estado {$respuesta->status()}");
        }

        $ofertas = $respuesta->json('pageProps.resultadosIniciales.ofertas');

        if (! is_array($ofertas)) {
            throw new RuntimeException('BuscoJobs: la respuesta de busqueda no tiene el formato esperado');
        }

        return array_map(fn (array $oferta) => $this->aOfertaDTO($oferta, $buildId), $ofertas);
    }

    /**
     * El buildId se lee de "__NEXT_DATA__" en el HTML de la propia pagina
     * de busqueda. Nunca se cachea ni se guarda en config: si cambio desde
     * la ultima corrida (el sitio se redesplego), esta llamada ya trae el
     * vigente.
     */
    private function obtenerBuildId(string $slug): string
    {
        $respuesta = Http::timeout(10)
            ->retry(2, 200, throw: false)
            ->get(self::URL_BASE.'/ofertas/'.rawurlencode($slug));

        if ($respuesta->failed()) {
            throw new RuntimeException("BuscoJobs: no se pudo cargar la pagina de busqueda ({$respuesta->status()})");
        }

        if (! preg_match('/"buildId":"([a-zA-Z0-9_-]+)"/', $respuesta->body(), $coincidencias)) {
            throw new RuntimeException('BuscoJobs: no se encontro un buildId valido en la pagina de busqueda');
        }

        return $coincidencias[1];
    }

    /**
     * BuscoJobs arma sus URLs de busqueda en minusculas, sin tildes, con
     * guiones en lugar de espacios y un guion bajo final (verificado:
     * "Soporte Tecnico" -> "soporte-tecnico_"). rawurlencode() de mas no
     * cambia nada valido pero evita que un caracter inesperado rompa la URL.
     */
    private function slugificar(string $termino): string
    {
        $normalizado = Str::of($termino)->lower()->ascii()->toString();
        $normalizado = trim((string) preg_replace('/[^a-z0-9]+/', '-', $normalizado), '-');

        return $normalizado.'_';
    }

    private function aOfertaDTO(array $oferta, string $buildId): OfertaDTO
    {
        return new OfertaDTO(
            externalId: (string) $oferta['IdOferta'],
            titulo: $oferta['CargoVacante'] ?? 'Sin título',
            empresa: $this->extraerEmpresa($oferta),
            departamento: $oferta['Departamento']['Nombre'] ?? null,
            esPublico: false,
            salarioVisible: false,
            salarioTexto: null,
            modalidad: $this->extraerModalidad($oferta),
            url: self::URL_BASE.'/oferta-ID-'.$oferta['IdOferta'],
            fechaPublicacion: $this->aFecha($oferta['FechaInicio'] ?? null),
            fechaCierre: null,
            descripcionCruda: $this->resolverDescripcion($oferta, $buildId),
        );
    }

    /**
     * El listado de busqueda trunca la descripcion a 150 caracteres (Fase
     * 9.1/9.2: verificado, no es un limite nuestro). Cuando eso pasa, se
     * intenta reemplazarla por la version completa del endpoint de
     * detalle; si no se puede, la truncada sigue siendo mejor que nada y
     * la oferta se guarda igual.
     */
    private function resolverDescripcion(array $oferta, string $buildId): ?string
    {
        $descripcionListado = $this->nuloSiVacio($oferta['Descripcion'] ?? null);
        $idOferta = $oferta['IdOferta'];

        if (! $this->pareceTruncada($descripcionListado)) {
            return $descripcionListado;
        }

        if (! array_key_exists($idOferta, $this->cacheDescripciones)) {
            $this->cacheDescripciones[$idOferta] = $this->obtenerDescripcionCompleta($buildId, $idOferta);
        }

        return $this->cacheDescripciones[$idOferta] ?? $descripcionListado;
    }

    private function pareceTruncada(?string $descripcion): bool
    {
        return $descripcion !== null
            && mb_strlen($descripcion) >= 150
            && str_ends_with(rtrim($descripcion), '...');
    }

    /**
     * Enriquecimiento opcional, nunca una condicion para guardar la oferta:
     * timeout corto, sin retry (a diferencia de las otras dos llamadas de
     * esta clase, esta puede fallar sin perder nada) y cualquier fallo
     * devuelve null para que resolverDescripcion() conserve la truncada.
     */
    private function obtenerDescripcionCompleta(string $buildId, int|string $idOferta): ?string
    {
        try {
            $respuesta = Http::timeout(5)->acceptJson()
                ->get(self::URL_BASE.'/_next/data/'.rawurlencode($buildId).'/es-UY/oferta-ID-'.rawurlencode((string) $idOferta).'.json');
        } catch (Throwable $e) {
            Log::warning("BuscoJobs: no se pudo obtener el detalle de la oferta {$idOferta}, se conserva la descripcion truncada", [
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        if ($respuesta->failed()) {
            Log::warning("BuscoJobs: el detalle de la oferta {$idOferta} respondio con estado {$respuesta->status()}, se conserva la descripcion truncada");

            return null;
        }

        $descripcion = $respuesta->json('pageProps.oferta.Descripcion');
        $descripcion = is_string($descripcion) ? $this->nuloSiVacio($descripcion) : null;

        if ($descripcion === null) {
            Log::warning("BuscoJobs: el detalle de la oferta {$idOferta} no incluyo una descripcion valida, se conserva la descripcion truncada");
        }

        return $descripcion;
    }

    /** Un empleador confidencial no debe mostrar nombre, tenga o no algo cargado en NombreEmpresa. */
    private function extraerEmpresa(array $oferta): ?string
    {
        if ((int) ($oferta['Confidencial'] ?? 0) === 1) {
            return null;
        }

        return $this->nuloSiVacio($oferta['NombreEmpresa'] ?? null);
    }

    /**
     * Prioridad determinista cuando ambos flags vienen en true: Remoto gana
     * sobre Hibrido. Quien puede trabajar 100% remoto puede, por
     * definicion, trabajar tambien de forma hibrida - Remoto es la
     * categoria mas amplia, no una contradiccion a resolver arbitrariamente.
     */
    private function extraerModalidad(array $oferta): string
    {
        if ((int) ($oferta['PermiteTeletrabajo'] ?? 0) === 1) {
            return 'Remoto';
        }

        if ((int) ($oferta['PermiteTrabajoHibrido'] ?? 0) === 1) {
            return 'Híbrido';
        }

        return 'Presencial';
    }

    private function aFecha(?string $fechaIso): ?string
    {
        if (blank($fechaIso)) {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($fechaIso)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function nuloSiVacio(?string $valor): ?string
    {
        return filled($valor) ? $valor : null;
    }
}
