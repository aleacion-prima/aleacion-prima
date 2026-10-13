<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('razon_social');
            // Cifrados con el cast `encrypted` del modelo (RNF-16); por eso son text.
            $table->text('rfc');
            $table->text('direccion_fiscal');
            $table->string('contacto_nombre');
            $table->string('contacto_correo');
            $table->string('contacto_telefono');
            $table->text('condiciones_pago');
            // Solo si el cliente tiene acceso al panel (chat, recortable).
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
