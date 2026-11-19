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
 * corre a mano en dev y en cada deploy (deploy.yml):
 *
 *   php artisan migrate --path=database/migrations/mongodb
 */
return new class extends Migration
{
    protected $connection = 'mongodb';

    public function up(): void
    {
        // table(), no create(): en producción la colección ya existe (la crea la
        // primera inserción de la bitácora). Solo agregamos los índices.
        Schema::connection('mongodb')->table('bitacora', function (Blueprint $collection): void {
            $collection->index(['lote_id' => 1, 'created_at' => 1]);
            $collection->index(['tipo' => 1, 'created_at' => -1]);
            $collection->index(['captura_tardia' => 1, 'created_at' => -1]);
        });
    }

    public function down(): void
    {
        // Borra solo los tres índices que creó up(), nunca la colección: la
        // bitácora es append-only y revertir no debe perder datos.
        Schema::connection('mongodb')->table('bitacora', function (Blueprint $collection): void {
            $collection->dropIndex(['lote_id' => 1, 'created_at' => 1]);
            $collection->dropIndex(['tipo' => 1, 'created_at' => -1]);
            $collection->dropIndex(['captura_tardia' => 1, 'created_at' => -1]);
        });
    }
};
