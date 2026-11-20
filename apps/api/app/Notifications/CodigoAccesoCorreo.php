<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Envía por correo el código de 6 dígitos del segundo factor. Usa el mismo
 * canal de correo que la recuperación de contraseña, sin dependencias nuevas.
 */
class CodigoAccesoCorreo extends Notification
{
    public function __construct(public readonly string $codigo) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $minutos = (int) config('segundo-factor.vigencia_minutos', 10);

        return (new MailMessage)
            ->subject('Tu código de acceso')
            ->greeting('Tu código de acceso')
            ->line("Usa este código para terminar de iniciar sesión: {$this->codigo}")
            ->line("Vence en {$minutos} minutos. Si no intentaste iniciar sesión, ignora este correo.");
    }
}
