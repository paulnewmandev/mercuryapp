<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable encargado de enviar el código OTP para restablecer contraseña.
 */
class PasswordResetOtpMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param User $user Usuario destinatario.
     * @param string $code Código OTP de seis dígitos.
     */
    public function __construct(
        protected User $user,
        protected string $code,
    ) {
    }

    /**
     * Construye el mensaje.
     *
     * @return $this
     */
    public function build(): self
    {
        return $this->subject('Código de verificación - MercuryApp')
            ->view('emails.passwordresetotp')
            ->with([
                'user_name' => $this->user->first_name . ' ' . $this->user->last_name,
                'reset_code' => $this->code,
            ]);
    }
}


