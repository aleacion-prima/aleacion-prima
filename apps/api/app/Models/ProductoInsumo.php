<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductoInsumo extends Pivot
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['cantidad_por_pieza' => 'decimal:4'];
    }
}
