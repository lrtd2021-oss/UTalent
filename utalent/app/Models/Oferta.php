<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Oferta extends Model
{
    use HasFactory;

    protected $fillable = [
        'fuente_id', 'external_id', 'titulo', 'empresa', 'departamento',
        'es_publico', 'salario_visible', 'salario_texto', 'modalidad', 'url',
        'fecha_publicacion', 'fecha_cierre', 'estado', 'descripcion_cruda',
    ];

    protected $casts = [
        'es_publico' => 'boolean',
        'salario_visible' => 'boolean',
        'fecha_publicacion' => 'date',
        'fecha_cierre' => 'date',
    ];

    public function fuente(): BelongsTo
    {
        return $this->belongsTo(Fuente::class);
    }
}
