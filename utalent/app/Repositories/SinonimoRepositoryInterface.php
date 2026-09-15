<?php

namespace App\Repositories;

interface SinonimoRepositoryInterface
{
    /** @return string[] Terminos equivalentes a $termino, incluido el mismo. */
    public function terminosRelacionados(string $termino): array;
}
