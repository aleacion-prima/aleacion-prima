<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('codigos_acceso', function (Blueprint $table) {
            $table->id();
            // Un código activo por usuario: al reenviar se reemplaza el anterior.
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            // El código se guarda hasheado, nunca en texto plano.
            $table->string('codigo');
            $table->timestamp('expira_en');
            $table->unsignedTinyInteger('intentos')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('codigos_acceso');
    }
};
