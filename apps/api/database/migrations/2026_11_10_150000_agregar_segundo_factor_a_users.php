<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Cada usuario activa o desactiva su segundo factor; arranca apagado.
            // Para el administrador es obligatorio por configuración
            // (SEGUNDO_FACTOR_ADMINISTRADOR, revisado en el login y en el perfil),
            // no por esta columna.
            $table->boolean('segundo_factor')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('segundo_factor');
        });
    }
};
