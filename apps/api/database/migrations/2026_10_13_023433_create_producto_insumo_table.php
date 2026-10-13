<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_insumo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained()->cascadeOnDelete();
            $table->foreignId('insumo_id')->constrained()->cascadeOnDelete();
            $table->decimal('cantidad_por_pieza', 12, 4);
            $table->timestamps();

            $table->unique(['producto_id', 'insumo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_insumo');
    }
};
