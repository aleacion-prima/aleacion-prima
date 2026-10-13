<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_insumo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insumo_id')->constrained();
            $table->string('tipo');
            $table->decimal('cantidad', 12, 3);
            $table->decimal('costo_unitario', 14, 4)->nullable();
            $table->string('lote_proveedor')->nullable();
            $table->string('proveedor')->nullable();
            $table->foreignId('lote_id')->nullable()->constrained();
            $table->text('motivo')->nullable();
            $table->foreignId('user_id')->constrained();
            $table->timestamp('ocurrido_en');
            $table->timestamps();

            $table->index(['insumo_id', 'ocurrido_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_insumo');
    }
};
