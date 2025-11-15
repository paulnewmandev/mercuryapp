<?php

namespace App\Services\Seo;

use App\Contracts\SeoMetaManagerContract;
use App\DataTransferObjects\SeoMetaData;

/**
 * Servicio encargado de centralizar la gestión de metadatos para SEO y SEM.
 *
 * @package App\Services\Seo
 */
class SeoMetaManager implements SeoMetaManagerContract
{
    /**
     * @param array<string, mixed> $defaults Configuración base definida en `config/seo.php`.
     */
    public function __construct(private readonly array $defaults)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function compose(array $overrides = []): SeoMetaData
    {
        $keywords = $this->normalizeKeywords(
            $overrides['keywords'] ?? $this->defaults['keywords'] ?? []
        );

        return new SeoMetaData(
            title: $overrides['title'] ?? $this->defaults['title'],
            description: $overrides['description'] ?? $this->defaults['description'],
            keywords: $keywords,
            canonicalUrl: $overrides['canonicalUrl'] ?? null,
            image: $overrides['image'] ?? $this->defaults['image'] ?? null,
        );
    }

    /**
     * Garantiza que las palabras clave se manipulen como arreglo indexado.
     *
     * @param array<int|string, string>|string $keywords Palabras clave crudas.
     *
     * @return array<int, string> Listado de palabras clave normalizado.
     */
    private function normalizeKeywords(array|string $keywords): array
    {
        if (is_string($keywords)) {
            $keywords = explode(',', $keywords);
        }

        return array_values(
            array_filter(
                array_map(static fn (string $keyword): string => trim($keyword), $keywords),
                static fn (string $keyword): bool => $keyword !== ''
            )
        );
    }
}


