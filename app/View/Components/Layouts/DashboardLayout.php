<?php

namespace App\View\Components\Layouts;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Componente de layout para las vistas del dashboard principal.
 *
 * @package App\View\Components\Layouts
 */
class DashboardLayout extends Component
{
    /**
     * @param array<string, mixed> $meta Metadatos para el layout base.
     */
    public function __construct(public array $meta = [])
    {
    }

    /**
     * Renderiza la vista asociada al componente.
     *
     * @return View
     */
    public function render(): View
    {
        return view('components.layouts.dashboard-layout');
    }
}


