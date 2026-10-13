<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ajustes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lote_area_id')->nullable()->constrained('lote_areas');
            $table->foreignId('lote_insumo_id')->nullable()->constrained('lote_insumos');
            $table->foreignId('insumo_id')->nullable()->constrained();
            $table->foreignId('producto_id')->nullable()->constrained();
            $table->string('campo');
            $table->jsonb('valores');
            $table->text('motivo');
            $table->foreignId('user_id')->constrained();
            $table->timestamps();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE ajustes ADD CONSTRAINT ajustes_un_objetivo CHECK (num_nonnulls(lote_area_id, lote_insumo_id, insumo_id, producto_id) = 1)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ajustes');
    }
};
