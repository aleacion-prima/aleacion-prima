<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedido_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained()->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained();
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 14, 4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_items');
    }
};
