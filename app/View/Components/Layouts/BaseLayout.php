<?php

namespace App\View\Components\Layouts;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Componente base que agrega metadatos SEO y assets globales.
 *
 * @package App\View\Components\Layouts
 */
class BaseLayout extends Component
{
    /**
     * @param array<string, mixed> $meta Metadatos a renderizar.
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
        return view('components.layouts.base-layout');
    }
}


