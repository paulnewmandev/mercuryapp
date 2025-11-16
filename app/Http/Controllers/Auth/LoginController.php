<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\SeoMetaManagerContract;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * Controlador responsable de presentar la pantalla de autenticación.
 *
 * @package App\Http\Controllers\Auth
 */
class LoginController extends Controller
{
    /**
     * @param SeoMetaManagerContract $seoMetaManager Gestor de metadatos SEO.
     */
    public function __construct(private readonly SeoMetaManagerContract $seoMetaManager)
    {
    }

    /**
     * Muestra la pantalla de inicio de sesión para MercuryApp.
     *
     * @return View Vista de autenticación con metadatos asociados.
     */
    public function __invoke(): View
    {
        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Login',
            'description' => 'Accede al panel de control profesional para talleres de reparación.',
        ])->toArray();

        return view('login', compact('meta'));
    }
}


