<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

/**
 * Índices de la colección `bitacora` (docs/modelo-datos.md).
 *
 * Vive en database/migrations/mongodb/ —fuera del directorio que corre el
 * `migrate` por defecto— porque el migrador no entra a subcarpetas. Así los
 * tests (RefreshDatabase sobre SQLite) nunca lo ejecutan ni tocan Mongo. Se
 * corre a mano en dev y en CI con Mongo:
 *
 *   php artisan migrate --path=database/migrations/mongodb
 */
return new class extends Migration
{
    protected $connection = 'mongodb';

    public function up(): void
    {
        Schema::connection('mongodb')->create('bitacora', function (Blueprint $collection): void {
            $collection->index(['lote_id' => 1, 'created_at' => 1]);
            $collection->index(['tipo' => 1, 'created_at' => -1]);
            $collection->index(['captura_tardia' => 1, 'created_at' => -1]);
        });
    }

    public function down(): void
    {
        Schema::connection('mongodb')->drop('bitacora');
    }
};
