<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Tenant\CurrentCompany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controlador responsable de cerrar la sesión del usuario autenticado.
 */
class LogoutController extends Controller
{
    /**
     * Cierra la sesión actual y redirige al formulario de inicio de sesión.
     *
     * @param Request $request Petición HTTP.
     *
     * @return RedirectResponse
     */
    public function __invoke(Request $request): RedirectResponse
    {
        Auth::guard()->logout();

        app(CurrentCompany::class)->clear();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

