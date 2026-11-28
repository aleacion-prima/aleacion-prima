<?php

namespace App\Listeners;

use App\Enums\Rol;
use App\Enums\Semaforo;
use App\Events\LoteActualizado;
use App\Lotes\EvaluadorSemaforo;
use App\Models\User;
use App\Notifications\LotePasoARojo;
use Illuminate\Support\Facades\Notification;

/**
 * Avisa a los administradores activos cuando un lote pasa a rojo en el semáforo.
 *
 * LoteActualizado se emite en cada cambio del lote, así que se guarda en el lote
 * el último color notificado y solo se avisa en la transición hacia rojo: seguir
 * en rojo no repite el aviso, y salir y volver a entrar en rojo sí vuelve a
 * avisar. El ámbar nunca notifica.
 */
class NotificarLoteRojo
{
    public function __construct(private EvaluadorSemaforo $evaluador) {}

    public function handle(LoteActualizado $evento): void
    {
        $lote = $evento->lote;

        // La notificación es para administradores, que sí ven costos: la regla
        // del costo debe contar al evaluar el color.
        $resultado = $this->evaluador->evaluar($lote, veCostos: true);

        if ($resultado === null) {
            // Lote cerrado: ya no tiene semáforo que vigilar.
            return;
        }

        $nivel = $resultado->semaforo;
        $anterior = $lote->semaforo_notificado;

        if ($nivel === Semaforo::Rojo && $anterior !== Semaforo::Rojo) {
            $admins = User::query()->role(Rol::Administrador)->where('activo', true)->get();

            Notification::send($admins, new LotePasoARojo(
                $lote->codigo,
                "El lote {$lote->codigo} pasó a rojo: {$resultado->motivos[0]}",
            ));
        }

        // Se guarda siempre el color actual cuando cambia. Guardar el lote directo
        // no redispara LoteActualizado (no hay observer de Lote), así que no hay
        // bucle de eventos.
        if ($anterior !== $nivel) {
            $lote->forceFill(['semaforo_notificado' => $nivel])->save();
        }
    }
}
