<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware que detecta y aplica automáticamente el idioma de la solicitud
 * considerando encabezados `Accept-Language` y preferencias persistentes.
 *
 * @package App\Http\Middleware
 */
class DetectLocale
{
    private const DEFAULT_LOCALE = 'es';
    private const AVAILABLE_LOCALES = ['es', 'en'];
    private const TEXT_DOMAIN = 'mercuryapp';

    /**
     * Maneja la solicitud entrante aplicando localización automática.
     *
     * @param Request $request Solicitud HTTP actual.
     * @param Closure(Request):Response $next Callback del siguiente middleware.
     *
     * @return Response Respuesta HTTP posterior al ajuste de localización.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);
        app()->setLocale($locale);
        $this->configureGettext($locale);

        return $next($request);
    }

    /**
     * Determina el idioma objetivo a partir de encabezados o parámetros.
     *
     * @param Request $request Solicitud HTTP a inspeccionar.
     *
     * @return string Código del idioma seleccionado.
     */
    private function resolveLocale(Request $request): string
    {
        $headerLocale = $request->getPreferredLanguage(self::AVAILABLE_LOCALES);

        if (is_string($headerLocale)) {
            return $headerLocale;
        }

        return self::DEFAULT_LOCALE;
    }

    /**
     * Configura gettext para que utilice los archivos de traducción definidos.
     *
     * @param string $locale Código de idioma a configurar.
     *
     * @return void
     */
    private function configureGettext(string $locale): void
    {
        $localeVariant = $this->buildLocaleVariant($locale);

        putenv("LC_ALL={$localeVariant}.UTF-8");
        setlocale(LC_ALL, "{$locale}.UTF-8", "{$localeVariant}.UTF-8");

        $localesPath = base_path('locales');
        bindtextdomain(self::TEXT_DOMAIN, $localesPath);
        bind_textdomain_codeset(self::TEXT_DOMAIN, 'UTF-8');
        textdomain(self::TEXT_DOMAIN);
    }

    /**
     * Construye una variante regional legible por las funciones de gettext.
     *
     * @param string $locale Código ISO de dos letras.
     *
     * @return string Variante regional compuesta.
     */
    private function buildLocaleVariant(string $locale): string
    {
        return sprintf('%s_%s', $locale, strtoupper($locale));
    }
}


