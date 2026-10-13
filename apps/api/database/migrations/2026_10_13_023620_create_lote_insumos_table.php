<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lote_insumos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('insumo_id')->constrained();
            $table->decimal('cantidad', 12, 3);
            $table->decimal('costo_unitario', 14, 4);
            $table->decimal('cantidad_devuelta', 12, 3)->default(0);
            $table->string('lote_proveedor')->nullable();
            $table->timestamps();

            $table->unique(['lote_id', 'insumo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lote_insumos');
    }
};
