<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sinonimo extends Model
{
    protected $fillable = ['termino', 'grupo'];

    /**
     * Normaliza el termino a minusculas antes de guardarlo, para que la
     * comparacion al buscar nunca dependa de como se escribio originalmente.
     */
    protected static function booted(): void
    {
        static::saving(function (Sinonimo $sinonimo) {
            $sinonimo->termino = mb_strtolower(trim($sinonimo->termino));
        });
    }

    /**
     * Todos los terminos equivalentes a $termino (incluido el mismo), para
     * poder buscar por cualquiera de sus sinonimos. Si el termino no esta
     * registrado, se busca solo por si mismo.
     *
     * @return string[]
     */
    public static function terminosEquivalentesA(string $termino): array
    {
        $termino = mb_strtolower(trim($termino));
        $grupo = static::where('termino', $termino)->value('grupo');

        return $grupo
            ? static::where('grupo', $grupo)->pluck('termino')->all()
            : [$termino];
    }
}
