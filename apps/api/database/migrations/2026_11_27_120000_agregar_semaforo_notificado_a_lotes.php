<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lotes', function (Blueprint $table) {
            // Último color del semáforo ya notificado al administrador. Nullable
            // porque un lote recién creado todavía no tiene color guardado.
            $table->string('semaforo_notificado')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('lotes', function (Blueprint $table) {
            $table->dropColumn('semaforo_notificado');
        });
    }
};
