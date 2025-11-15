<?php

namespace App\View\Components\Dashboard;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Tabla resumida de tickets recientes en el dashboard.
 *
 * @package App\View\Components\Dashboard
 */
class TicketTable extends Component
{
    /**
     * @param array<int, array<string, mixed>> $tickets Colección de tickets.
     */
    public function __construct(public array $tickets = [])
    {
    }

    /**
     * Renderiza la vista del componente.
     *
     * @return View
     */
    public function render(): View
    {
        return view('components.dashboard.TicketTable');
    }
}


