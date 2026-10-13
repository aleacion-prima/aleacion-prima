<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->jsonb('especificaciones')->nullable();
            // Nunca salen por la API pública ni por el canal `catalogo` (RNF-04).
            $table->decimal('precio_mayoreo', 14, 4);
            $table->decimal('precio_menudeo', 14, 4);
            $table->integer('existencia')->default(0);
            $table->boolean('publicado')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
