<?php

namespace App\IA;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Unica clase del proyecto que conoce el endpoint, los headers, la API key,
 * el modelo, el prompt y el formato de respuesta de OpenRouter. Ningun
 * Controller, Repository, Model, Strategy de fuente ni Job debe conocer
 * nada de esto.
 *
 * Nunca lanza una excepcion por un fallo esperable del proveedor (sin
 * configurar, timeout, error HTTP, respuesta invalida): en esos casos
 * registra el problema y devuelve null.
 */
class OpenRouterNormalizadorIA implements NormalizadorIAInterface
{
    private const ENDPOINT = 'https://openrouter.ai/api/v1/chat/completions';

    private const SENIORITY_VALIDOS = ['junior', 'semi_senior', 'senior'];

    private const MAX_TECNOLOGIAS = 15;

    private const PROMPT_SISTEMA = <<<'PROMPT'
        Sos un asistente que extrae datos estructurados de avisos de empleo en tecnologia.
        Responde EXCLUSIVAMENTE con un objeto JSON valido, sin texto adicional y sin bloques de codigo markdown, con exactamente esta forma:
        {"seniority": "junior" | "semi_senior" | "senior" | null, "tecnologias": ["..."]}
        Si no podes determinar el seniority, usa null. Si no identificas tecnologias, usa un array vacio.
        PROMPT;

    private readonly ?string $apiKey;

    private readonly ?string $modelo;

    public function __construct()
    {
        $this->apiKey = config('services.openrouter.key');
        $this->modelo = config('services.openrouter.modelo');
    }

    public function normalizar(string $titulo, ?string $descripcionCruda): ?OfertaNormalizada
    {
        if (blank($this->apiKey)) {
            Log::warning('NormalizadorIA: OPENROUTER_API_KEY no esta configurada, se omite la normalizacion.');

            return null;
        }

        try {
            $respuesta = Http::withToken($this->apiKey)
                ->timeout(15)
                ->post(self::ENDPOINT, $this->armarPayload($titulo, $descripcionCruda));
        } catch (ConnectionException $e) {
            Log::warning('NormalizadorIA: no se pudo conectar con OpenRouter', ['error' => $e->getMessage()]);

            return null;
        }

        if ($respuesta->failed()) {
            Log::warning('NormalizadorIA: OpenRouter respondio con error', [
                'status' => $respuesta->status(),
                'body' => $respuesta->body(),
            ]);

            return null;
        }

        $contenido = $respuesta->json('choices.0.message.content');
        $datos = is_string($contenido) ? $this->extraerJson($contenido) : null;

        if ($datos === null) {
            Log::warning('NormalizadorIA: la respuesta de OpenRouter no es JSON valido', ['contenido' => $contenido]);

            return null;
        }

        return $this->validar($datos);
    }

    private function armarPayload(string $titulo, ?string $descripcionCruda): array
    {
        $contenidoUsuario = "Titulo: {$titulo}";

        if (filled($descripcionCruda)) {
            $contenidoUsuario .= "\nDescripcion: {$descripcionCruda}";
        }

        return [
            'model' => $this->modelo,
            'messages' => [
                ['role' => 'system', 'content' => self::PROMPT_SISTEMA],
                ['role' => 'user', 'content' => $contenidoUsuario],
            ],
        ];
    }

    /** Algunos modelos envuelven el JSON en un bloque ```json a pesar del prompt. */
    private function extraerJson(string $contenido): ?array
    {
        $contenido = trim($contenido);
        $contenido = preg_replace('/^```(?:json)?|```$/i', '', $contenido);
        $datos = json_decode(trim($contenido), true);

        return is_array($datos) ? $datos : null;
    }

    private function validar(array $datos): OfertaNormalizada
    {
        $seniority = $datos['seniority'] ?? null;

        if (! in_array($seniority, self::SENIORITY_VALIDOS, true)) {
            $seniority = null;
        }

        $tecnologias = $datos['tecnologias'] ?? null;

        if (is_array($tecnologias)) {
            $tecnologias = collect($tecnologias)
                ->filter(fn ($tecnologia) => is_string($tecnologia) && trim($tecnologia) !== '')
                ->map(fn ($tecnologia) => trim($tecnologia))
                ->unique()
                ->take(self::MAX_TECNOLOGIAS)
                ->values()
                ->all();
        } else {
            $tecnologias = null;
        }

        return new OfertaNormalizada($seniority, $tecnologias);
    }
}
