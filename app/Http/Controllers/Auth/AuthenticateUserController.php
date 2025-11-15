<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use App\Support\Tenant\CurrentCompany;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controlador responsable de autenticar al usuario y gestionar la opción "Recordarme".
 */
class AuthenticateUserController extends Controller
{
    /**
     * Maneja el intento de inicio de sesión utilizando las credenciales enviadas.
     *
     * @param Request $request Petición HTTP con los datos del formulario.
     * @param StatefulGuard|null $guard Guard web, inyectable para facilitar pruebas.
     *
     * @return RedirectResponse
     */
    public function __invoke(Request $request, ?StatefulGuard $guard = null): RedirectResponse
    {
        $validated = $request->validate(
            [
                'email' => ['required', 'email'],
                'password' => ['required', 'min:8'],
            ],
            [
                'email.required' => 'El correo electrónico es obligatorio.',
                'email.email' => 'Ingresa un correo electrónico válido.',
                'password.required' => 'La contraseña es obligatoria.',
                'password.min' => 'La contraseña debe tener al menos :min caracteres.',
            ]
        );

        $remember = $request->boolean('remember');
        $guard ??= Auth::guard();

        if (! $guard->attempt(['email' => $validated['email'], 'password' => $validated['password']], $remember)) {
            return back()
                ->withErrors(['credentials' => 'Correo o contraseña incorrectos.'])
                ->withInput($request->except('password'));
        }

        $request->session()->regenerate();

        /** @var \App\Models\User $user */
        $user = $guard->user();

        app(CurrentCompany::class)->set($user->company_id);
        NotificationHelper::record(
            $user->company_id,
            'Inicio de sesión exitoso.',
            $user->id
        );

        return redirect()->intended(route('dashboard'));
    }
}

