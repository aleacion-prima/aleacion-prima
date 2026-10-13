<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Libro mayor de producto terminado; productos.existencia es su suma.
        Schema::create('movimientos_producto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained();
            // Valores de App\Enums\TipoMovimientoProducto.
            $table->string('tipo');
            // Con signo: produccion +; venta −; ajuste ±.
            $table->integer('cantidad');
            $table->foreignId('lote_id')->nullable()->constrained();
            $table->foreignId('pedido_id')->nullable()->constrained();
            $table->text('motivo')->nullable();
            $table->foreignId('user_id')->constrained();
            $table->timestamp('ocurrido_en');
            $table->timestamps();

            $table->index(['producto_id', 'ocurrido_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_producto');
    }
};
