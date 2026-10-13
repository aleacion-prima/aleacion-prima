<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('nombre')->unique();
            $table->text('descripcion')->nullable();
            $table->decimal('tarifa_mano_obra_hora', 14, 4);
            $table->decimal('costo_maquina_hora', 14, 4);
            $table->boolean('obligatoria')->default(false);
            $table->integer('orden_catalogo');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('areas');
    }
};
