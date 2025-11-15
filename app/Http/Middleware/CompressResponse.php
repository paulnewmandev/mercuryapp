<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware encargado de comprimir las respuestas HTTP compatibles utilizando gzip
 * para mejorar el desempeño percibido de la aplicación.
 *
 * @package App\Http\Middleware
 */
class CompressResponse
{
    private const SUPPORTED_ENCODINGS = ['gzip', 'x-gzip'];
    private const COMPRESSIBLE_CONTENT_TYPES = [
        'text/html',
        'text/css',
        'text/javascript',
        'application/javascript',
        'application/json',
        'application/xml',
        'image/svg+xml',
    ];

    /**
     * Procesa la solicitud y aplica compresión cuando es viable.
     *
     * @param Request $request Solicitud HTTP entrante.
     * @param Closure(Request):Response $next Callback del pipeline.
     *
     * @return Response Respuesta original o comprimida.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (! $this->shouldCompress($request, $response)) {
            return $response;
        }

        $compressedContent = gzencode($response->getContent() ?? '', 6);

        if ($compressedContent === false) {
            return $response;
        }

        $response->setContent($compressedContent);
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Vary', trim($response->headers->get('Vary').', Accept-Encoding', ' ,'));
        $response->headers->set('Content-Length', (string) strlen($compressedContent));

        return $response;
    }

    /**
     * Determina si la respuesta debe ser comprimida.
     *
     * @param Request $request Solicitud HTTP evaluada.
     * @param Response $response Respuesta generada por la aplicación.
     *
     * @return bool Indicador de compresión.
     */
    private function shouldCompress(Request $request, Response $response): bool
    {
        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            return false;
        }

        if ($response->headers->has('Content-Encoding')) {
            return false;
        }

        if ($request->isMethod('HEAD')) {
            return false;
        }

        if (! $this->clientSupportsCompression($request)) {
            return false;
        }

        return $this->isCompressibleContentType($response);
    }

    /**
     * Verifica si el cliente soporta al menos un algoritmo de compresión.
     *
     * @param Request $request Solicitud HTTP.
     *
     * @return bool Verdadero si el encabezado Accept-Encoding es compatible.
     */
    private function clientSupportsCompression(Request $request): bool
    {
        $acceptEncoding = strtolower($request->header('Accept-Encoding', ''));

        foreach (self::SUPPORTED_ENCODINGS as $encoding) {
            if (str_contains($acceptEncoding, $encoding)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Evalúa si el tipo de contenido es apto para compresión.
     *
     * @param Response $response Respuesta a evaluar.
     *
     * @return bool Verdadero cuando el tipo de contenido es comprimible.
     */
    private function isCompressibleContentType(Response $response): bool
    {
        $contentType = $response->headers->get('Content-Type', '');

        foreach (self::COMPRESSIBLE_CONTENT_TYPES as $compressibleType) {
            if (str_starts_with($contentType, $compressibleType)) {
                return true;
            }
        }

        return false;
    }
}


