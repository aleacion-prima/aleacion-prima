<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lote_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('area_id')->constrained();
            $table->integer('orden');
            $table->decimal('tarifa_mano_obra_hora', 14, 4);
            $table->decimal('costo_maquina_hora', 14, 4);
            $table->integer('piezas_recibidas')->nullable();
            $table->timestamp('entrada_ocurrido_en')->nullable();
            $table->timestamp('entrada_registrado_en')->nullable();
            $table->foreignId('entrada_user_id')->nullable()->constrained('users');
            $table->integer('piezas_entregadas')->nullable();
            $table->integer('merma_piezas')->nullable();
            $table->decimal('merma_material', 12, 3)->nullable();
            $table->decimal('retorno_kg', 12, 3)->nullable();
            $table->timestamp('salida_ocurrido_en')->nullable();
            $table->timestamp('salida_registrado_en')->nullable();
            $table->foreignId('salida_user_id')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique(['lote_id', 'orden']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE lote_areas ADD CONSTRAINT lote_areas_piezas_entregadas_check CHECK (piezas_entregadas IS NULL OR piezas_recibidas IS NULL OR piezas_entregadas <= piezas_recibidas)');
            DB::statement('ALTER TABLE lote_areas ADD CONSTRAINT lote_areas_salida_check CHECK (salida_ocurrido_en IS NULL OR entrada_ocurrido_en IS NULL OR salida_ocurrido_en >= entrada_ocurrido_en)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lote_areas');
    }
};
