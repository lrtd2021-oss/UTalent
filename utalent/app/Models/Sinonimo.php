<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sinonimo extends Model
{
    protected $fillable = ['termino', 'grupo'];

    /**
     * Todos los terminos equivalentes a $termino (incluido el mismo), para
     * poder buscar por cualquiera de sus sinonimos. Si el termino no esta
     * registrado, se busca solo por si mismo.
     *
     * @return string[]
     */
    public static function terminosEquivalentesA(string $termino): array
    {
        $grupo = static::where('termino', $termino)->value('grupo');

        return $grupo
            ? static::where('grupo', $grupo)->pluck('termino')->all()
            : [$termino];
    }
}
