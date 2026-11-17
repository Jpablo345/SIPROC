<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Spatie\OneTimePasswords\Models\OneTimePassword;

/**
 * Correo con el código para restablecer la contraseña. Usa el mismo sistema de códigos
 * del registro (spatie/laravel-one-time-passwords), pero con su propio texto.
 */
class CodigoRestablecerContrasena extends Notification
{
    use Queueable;

    public function __construct(public OneTimePassword $codigo, public int $minutos) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Plantilla propia: resources/views/vendor/notifications/email.blade.php está escrita para el enlace antiguo.
        return (new MailMessage)
            ->subject($this->codigo->password.' es tu código para restablecer la contraseña')
            ->markdown('mail.codigo-restablecer', [
                'codigo' => $this->codigo->password,
                'minutos' => $this->minutos,
                'nombre' => $notifiable->name,
            ]);
    }
}
