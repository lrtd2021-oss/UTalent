<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ofertas', function (Blueprint $table) {
            $table->string('seniority')->nullable()->after('descripcion_cruda');
            $table->json('tecnologias')->nullable()->after('seniority');
            // Evita volver a llamar a NormalizadorIA para una oferta ya procesada.
            $table->boolean('ia_normalizado')->default(false)->after('tecnologias');
        });
    }

    public function down(): void
    {
        Schema::table('ofertas', function (Blueprint $table) {
            $table->dropColumn(['seniority', 'tecnologias', 'ia_normalizado']);
        });
    }
};
