<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\MailHelper;
use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserToken;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Controlador encargado de gestionar el flujo de restablecimiento de contraseña mediante OTP.
 */
class PasswordResetController extends Controller
{
    /**
     * Muestra el formulario para solicitar el restablecimiento de contraseña.
     *
     * @return View
     */
    public function showEmailForm(): View
    {
        return view('auth.passwords.forgot');
    }

    /**
     * Genera y envía el código OTP al correo del usuario.
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function sendOtp(Request $request, bool $json = false): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            return back()
                ->withErrors(['email' => 'No existe una cuenta registrada con ese correo.'])
                ->withInput();
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        UserToken::where('user_id', $user->id)->delete();

        UserToken::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'token' => Hash::make($code),
            'expires_at' => Carbon::now()->addMinutes(10),
            'created_at' => Carbon::now(),
        ]);

        MailHelper::sendTemplate(
            $user->email,
            'Código de verificación - MercuryApp',
            'Emails.PasswordResetOtp',
            [
                'user_name' => $user->first_name . ' ' . $user->last_name,
                'reset_code' => $code,
            ]
        );

        NotificationHelper::record(
            $user->company_id,
            'Se envió un código OTP para restablecer la contraseña.',
            $user->id
        );

        if ($json) {
            return response()->json(['status' => 'Código enviado'], 200);
        }

        return redirect()
            ->route('password.otp', ['email' => $user->email])
            ->with('status', 'Te enviamos un código de verificación a tu correo.');
    }

    /**
     * Muestra el formulario para ingresar el código OTP.
     *
     * @param Request $request
     *
     * @return View|RedirectResponse
     */
    public function showOtpForm(Request $request): View|RedirectResponse
    {
        $email = $request->query('email') ?? old('email');

        if (! $email) {
            return redirect()->route('password.request');
        }

        return view('Auth.Passwords.Otp', ['email' => $email]);
    }

    /**
     * Verifica el código OTP ingresado por el usuario.
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function verifyOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'code.required' => 'Ingresa el código que te enviamos.',
            'code.digits' => 'El código debe tener exactamente 6 dígitos.',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            return back()
                ->withErrors(['email' => 'No existe una cuenta registrada con ese correo.'])
                ->withInput();
        }

        $tokens = UserToken::where('user_id', $user->id)
            ->where('expires_at', '>=', Carbon::now())
            ->orderByDesc('created_at')
            ->get();

        $matchedToken = $tokens->first(function (UserToken $token) use ($validated) {
            return Hash::check($validated['code'], $token->token);
        });

        if (! $matchedToken) {
            return back()
                ->withErrors(['code' => 'El código es inválido o ha expirado.'])
                ->withInput();
        }

        session([
            'password_reset_user_id' => $user->id,
            'password_reset_token_id' => $matchedToken->id,
        ]);

        NotificationHelper::record(
            $user->company_id,
            'Código OTP verificado exitosamente.',
            $user->id
        );

        return redirect()
            ->route('password.reset.form')
            ->with('status', 'Código verificado correctamente. Ahora ingresa tu nueva contraseña.');
    }

    /**
     * Muestra el formulario para establecer una nueva contraseña.
     *
     * @return View|RedirectResponse
     */
    public function showResetForm(): View|RedirectResponse
    {
        if (! session()->has('password_reset_user_id')) {
            return redirect()->route('password.request');
        }

        return view('auth.passwords.reset');
    }

    /**
     * Actualiza la contraseña del usuario tras validar el OTP.
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function resetPassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', 'min:8'],
        ], [
            'password.required' => 'La nueva contraseña es obligatoria.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos :min caracteres.',
        ]);

        $userId = session('password_reset_user_id');
        $tokenId = session('password_reset_token_id');

        if (! $userId || ! $tokenId) {
            return redirect()->route('password.request');
        }

        $token = UserToken::where('id', $tokenId)
            ->where('user_id', $userId)
            ->where('expires_at', '>=', Carbon::now())
            ->first();

        if (! $token) {
            session()->forget(['password_reset_user_id', 'password_reset_token_id']);

            return redirect()
                ->route('password.request')
                ->withErrors(['email' => 'El enlace de recuperación ha expirado. Intenta de nuevo.']);
        }

        $user = User::find($userId);

        if (! $user) {
            session()->forget(['password_reset_user_id', 'password_reset_token_id']);

            return redirect()
                ->route('password.request')
                ->withErrors(['email' => 'No se encontró el usuario asociado.']);
        }

        $newPassword = $validated['password'];

        $user->password_hash = Hash::make($newPassword);
        $user->save();

        UserToken::where('user_id', $user->id)->delete();
        session()->forget(['password_reset_user_id', 'password_reset_token_id']);

        MailHelper::sendTemplate(
            $user->email,
            'Tu contraseña fue actualizada - MercuryApp',
            'Emails.PasswordResetSuccess',
            [
                'user_name' => $user->first_name . ' ' . $user->last_name,
                'user_email' => $user->email,
                'new_password' => $newPassword,
                'login_url' => rtrim(config('app.url'), '/') . '/login',
            ]
        );

        NotificationHelper::record(
            $user->company_id,
            'La contraseña del usuario fue restablecida correctamente.',
            $user->id
        );

        return redirect()
            ->route('login')
            ->with('status', 'Tu contraseña ha sido restablecida correctamente. Ya puedes iniciar sesión.');
    }
}


