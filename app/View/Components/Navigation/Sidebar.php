<?php

namespace App\View\Components\Navigation;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Sidebar principal reutilizable del dashboard.
 *
 * @package App\View\Components\Navigation
 */
class Sidebar extends Component
{
    /**
     * Devuelve la vista asociada al componente.
     *
     * @return View
     */
    public function render(): View
    {
        return view('components.navigation.sidebar');
    }
}


