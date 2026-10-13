<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained();
            $table->string('estado');
            $table->string('estado_pago');
            $table->text('notas')->nullable();
            $table->foreignId('creado_por')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
