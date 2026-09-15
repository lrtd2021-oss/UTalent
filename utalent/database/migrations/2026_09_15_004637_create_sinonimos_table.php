<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sinonimos', function (Blueprint $table) {
            $table->id();
            // Termino de busqueda, ej: "developer".
            $table->string('termino')->unique();
            // Los terminos que comparten grupo se consideran equivalentes,
            // ej: "programador" y "developer" en el grupo "programador".
            $table->string('grupo');
            $table->timestamps();

            $table->index('grupo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sinonimos');
    }
};
