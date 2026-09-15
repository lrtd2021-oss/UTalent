<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Oferta extends Model
{
    use HasFactory;

    /**
     * Umbral de antiguedad (Fase 9.1): a partir de esta cantidad de dias
     * desde su publicacion, una oferta se marca como "antigua" solo como
     * señal visual. No afecta el campo `estado` en absoluto: el cierre real
     * sigue dependiendo unicamente del barrido completo de BuscadorService
     * (Fase 6). Se eligio 180 dias (~6 meses) porque es el punto en el que
     * una convocatoria de tecnologia deja de leerse como una oportunidad
     * reciente, independientemente de cuanto tiempo la fuente original la
     * siga reportando como vigente.
     */
    private const DIAS_ANTIGUEDAD = 180;

    protected $fillable = [
        'fuente_id', 'external_id', 'titulo', 'empresa', 'departamento',
        'es_publico', 'salario_visible', 'salario_texto', 'modalidad', 'url',
        'fecha_publicacion', 'fecha_cierre', 'estado', 'descripcion_cruda',
        'seniority', 'tecnologias', 'ia_normalizado',
    ];

    protected $appends = ['es_antigua'];

    protected $casts = [
        'es_publico' => 'boolean',
        'salario_visible' => 'boolean',
        'fecha_publicacion' => 'date',
        'fecha_cierre' => 'date',
        'tecnologias' => 'array',
        'ia_normalizado' => 'boolean',
    ];

    public function fuente(): BelongsTo
    {
        return $this->belongsTo(Fuente::class);
    }

    public function getEsAntiguaAttribute(): bool
    {
        // Se compara a nivel de dia (no de instante exacto): fecha_publicacion
        // ya se guarda sin hora (cast 'date'), y today() evita que el umbral
        // varie segun la hora del dia en que se evalue.
        return $this->fecha_publicacion !== null
            && $this->fecha_publicacion->lt(today()->subDays(self::DIAS_ANTIGUEDAD));
    }
}
