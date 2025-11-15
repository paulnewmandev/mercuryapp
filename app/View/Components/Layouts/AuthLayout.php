<?php

namespace App\View\Components\Layouts;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Componente de layout para pantallas de autenticación.
 *
 * @package App\View\Components\Layouts
 */
class AuthLayout extends Component
{
    /**
     * @param array<string, mixed> $meta Metadatos a transmitir al layout base.
     */
    public function __construct(public array $meta = [])
    {
    }

    /**
     * Obtiene la vista asociada al componente.
     *
     * @return View
     */
    public function render(): View
    {
        return view('components.layouts.AuthLayout');
    }
}


