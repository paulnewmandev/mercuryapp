<?php

namespace App\View\Components\Navigation;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Barra superior del dashboard con buscador y acciones rápidas.
 *
 * @package App\View\Components\Navigation
 */
class Topbar extends Component
{
    /**
     * Renderiza la vista correspondiente al componente.
     *
     * @return View
     */
    public function render(): View
    {
        return view('components.navigation.Topbar');
    }
}


