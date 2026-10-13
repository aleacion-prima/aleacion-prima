<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insumos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('nombre')->unique();
            $table->string('categoria');
            $table->string('unidad');
            $table->decimal('costo_unitario', 14, 4);
            $table->decimal('existencia', 12, 3)->default(0);
            $table->decimal('stock_minimo', 12, 3)->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insumos');
    }
};
