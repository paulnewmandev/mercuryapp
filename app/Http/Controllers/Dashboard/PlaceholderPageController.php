<?php

namespace App\Http\Controllers\Dashboard;

use App\Contracts\SeoMetaManagerContract;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Controlador que renderiza páginas estáticas del panel hasta que cuenten con contenido real.
 */
class PlaceholderPageController extends Controller
{
    /**
     * @param SeoMetaManagerContract $seoMetaManager Gestor de metadatos SEO.
     */
    public function __construct(private readonly SeoMetaManagerContract $seoMetaManager)
    {
    }

    /**
     * Renderiza una vista temporal utilizando la configuración centralizada de navegación.
     *
     * @param Request $request Petición HTTP entrante.
     * @param string $pageKey Identificador de la página configurada.
     *
     * @return View
     */
    public function __invoke(Request $request, string $pageKey): View
    {
        $pages = config('navigation.pages', []);

        if (! array_key_exists($pageKey, $pages)) {
            abort(404);
        }

        $page = $pages[$pageKey];
        $title = $page['title'];
        $parentLabel = $page['parent'] ?? null;

        $meta = $this->seoMetaManager->compose([
            'title' => sprintf('MercuryApp · %s', $title),
            'description' => sprintf('Sección en preparación: %s.', $title),
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
        ];

        if (! empty($parentLabel)) {
            $breadcrumbItems[] = ['label' => $parentLabel];
        }

        $breadcrumbItems[] = ['label' => $title];

        return view('PagePlaceholder', [
            'meta' => $meta,
            'title' => $title,
            'breadcrumbItems' => $breadcrumbItems,
        ]);
    }
}
