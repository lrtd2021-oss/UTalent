<?php

namespace App\IA;

/**
 * Unico punto de contacto del sistema con un modelo de lenguaje. Ninguna
 * otra clase (Controller, Repository, Model, Strategy de fuente o Job)
 * debe conocer nada del proveedor de IA.
 *
 * Implementaciones: nunca deben lanzar una excepcion hacia quien las llama
 * ante un fallo del proveedor (timeout, error HTTP, respuesta invalida). En
 * ese caso devuelven null: la oferta se guarda igual, sin enriquecer.
 */
interface NormalizadorIAInterface
{
    public function normalizar(string $titulo, ?string $descripcionCruda): ?OfertaNormalizada;
}
