<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // La pregunta es texto visible; la respuesta se guarda hasheada.
            $table->string('pregunta_secreta')->nullable();
            $table->string('respuesta_secreta')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['pregunta_secreta', 'respuesta_secreta']);
        });
    }
};
