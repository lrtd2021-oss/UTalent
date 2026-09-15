<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fuente extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'nombre_visible', 'tipo', 'activa'];

    protected $casts = [
        'activa' => 'boolean',
    ];

    public function ofertas(): HasMany
    {
        return $this->hasMany(Oferta::class);
    }
}
