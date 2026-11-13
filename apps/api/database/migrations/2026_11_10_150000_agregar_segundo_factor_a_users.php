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
            // Para el administrador es obligatorio: no puede desactivarlo (se valida
            // en SegundoFactorController), pero los seeders lo dejan apagado para no
            // romper el login automático de desarrollo y las pruebas E2E.
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
