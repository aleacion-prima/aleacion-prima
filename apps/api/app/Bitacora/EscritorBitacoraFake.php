<?php

namespace App\Bitacora;

use App\Enums\TipoEventoBitacora;
use PHPUnit\Framework\Assert;
use RuntimeException;

/**
 * Escritor de pruebas: solo guarda en memoria el documento que se le pide
 * escribir. El after-commit vive en Bitacora::registrar(), así que el fake
 * recibe únicamente los eventos de operaciones que hacen commit (los de una
 * operación revertida nunca llegan).
 *
 * Con fallar() simula que Mongo está caído, para comprobar que un fallo de la
 * bitácora no revierte la operación del lote.
 */
class EscritorBitacoraFake implements EscritorBitacora
{
    /**
     * @var list<array<string, mixed>>
     */
    public array $registros = [];

    private bool $falla = false;

    /**
     * @param  array<string, mixed>  $doc
     */
    public function escribir(array $doc): void
    {
        if ($this->falla) {
            throw new RuntimeException('Mongo caído (fake)');
        }

        $this->registros[] = $doc;
    }

    /**
     * Activa el modo de fallo: la siguiente escritura lanza una excepción.
     */
    public function fallar(): self
    {
        $this->falla = true;

        return $this;
    }

    /**
     * Tipos registrados, en orden de registro.
     *
     * @return list<string>
     */
    public function tipos(): array
    {
        return array_map(
            fn (array $doc): string => (string) $doc['tipo'],
            $this->registros,
        );
    }

    /**
     * @param  (callable(array<string, mixed>): bool)|null  $cb
     */
    public function assertRegistrado(TipoEventoBitacora $tipo, ?callable $cb = null): void
    {
        $coincidencias = array_filter(
            $this->registros,
            fn (array $doc): bool => $doc['tipo'] === $tipo->value && ($cb === null || $cb($doc)),
        );

        Assert::assertNotEmpty(
            $coincidencias,
            "No se registró ningún evento de bitácora de tipo '{$tipo->value}' que cumpla la condición.",
        );
    }

    public function assertNingunRegistro(): void
    {
        Assert::assertSame([], $this->registros, 'Se esperaba que la bitácora no registrara nada.');
    }
}
