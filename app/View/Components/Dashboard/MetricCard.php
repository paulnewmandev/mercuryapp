<?php

namespace App\View\Components\Dashboard;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Tarjeta de métrica para el panel principal.
 *
 * @package App\View\Components\Dashboard
 */
class MetricCard extends Component
{
    /**
     * @param string $label Etiqueta descriptiva.
     * @param string $value Valor principal desplegado.
     * @param string|null $trend Tendencia opcional.
     */
    public function __construct(
        public string $label,
        public string $value,
        public ?string $trend = null,
    ) {
    }

    /**
     * Devuelve la vista asociada al componente.
     *
     * @return View
     */
    public function render(): View
    {
        return view('components.dashboard.metric-card');
    }
}


