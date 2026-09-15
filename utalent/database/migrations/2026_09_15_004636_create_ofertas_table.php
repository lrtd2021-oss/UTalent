<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ofertas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fuente_id')->constrained()->cascadeOnDelete();
            // Id de la oferta en el sitio de origen. Junto a fuente_id evita
            // guardar la misma oferta dos veces si se vuelve a importar.
            $table->string('external_id');
            $table->string('titulo');
            $table->string('empresa')->nullable();
            $table->string('departamento')->nullable();
            $table->boolean('es_publico');
            $table->boolean('salario_visible')->default(false);
            $table->string('salario_texto')->nullable();
            $table->string('modalidad')->nullable();
            $table->string('url');
            $table->date('fecha_publicacion')->nullable();
            $table->date('fecha_cierre')->nullable();
            $table->enum('estado', ['activa', 'cerrada'])->default('activa');
            $table->text('descripcion_cruda')->nullable();
            $table->timestamps();

            $table->unique(['fuente_id', 'external_id']);
            $table->index('titulo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ofertas');
    }
};
