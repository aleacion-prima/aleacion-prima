<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lotes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->foreignId('producto_id')->constrained();
            $table->foreignId('pedido_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('cantidad_objetivo');
            $table->string('estado');
            $table->foreignId('area_actual_id')->nullable()->constrained('areas');
            $table->integer('piezas_terminadas')->nullable();
            $table->decimal('costo_material', 14, 4)->default(0);
            $table->decimal('costo_mano_obra', 14, 4)->default(0);
            $table->decimal('costo_maquina', 14, 4)->default(0);
            $table->decimal('costo_total', 14, 4)->default(0);
            $table->decimal('costo_por_pieza', 14, 4)->nullable();
            $table->foreignId('creado_por')->constrained('users');
            $table->foreignId('confirmado_por')->nullable()->constrained('users');
            $table->timestamp('confirmado_en')->nullable();
            $table->foreignId('cancelado_por')->nullable()->constrained('users');
            $table->timestamp('cancelado_en')->nullable();
            $table->text('motivo_cancelacion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lotes');
    }
};
