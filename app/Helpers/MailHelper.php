<?php

namespace App\Helpers;

use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;

/**
 * Helper centralizado para envío de correos basados en plantillas.
 */
class MailHelper
{
    /**
     * Envía un correo utilizando una vista como plantilla.
     *
     * @param string|array<string, string> $to Destinatario (correo o arreglo con address/name).
     * @param string $subject Asunto del correo.
     * @param string $view Vista blade que se usará como plantilla.
     * @param array<string, mixed> $data Datos que se inyectarán en la vista.
     * @param callable|null $callback Callback opcional para personalizar el mensaje.
     *
     * @return void
     */
    public static function sendTemplate(string|array $to, string $subject, string $view, array $data = [], ?callable $callback = null): void
    {
        Mail::send($view, $data, function (Message $message) use ($to, $subject, $callback): void {
            if (is_array($to)) {
                $message->to($to['address'] ?? '', $to['name'] ?? null);
            } else {
                $message->to($to);
            }

            $message->subject($subject);

            if ($callback) {
                $callback($message);
            }
        });
    }
}


