<?php

namespace App\DataTransferObjects;

/**
 * DTO responsable de transportar los metadatos SEO normalizados.
 *
 * @package App\DataTransferObjects
 */
class SeoMetaData
{
    /**
     * @param string $title Título de la página.
     * @param string $description Descripción corta optimizada para motores de búsqueda.
     * @param array<int, string> $keywords Palabras clave relevantes.
     * @param string|null $canonicalUrl URL canónica opcional.
     * @param string|null $image Ruta absoluta o relativa de la imagen principal.
     */
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly array $keywords,
        public readonly ?string $canonicalUrl = null,
        public readonly ?string $image = null,
    ) {
    }

    /**
     * Representa el DTO como arreglo para facilitar su consumo en las vistas.
     *
     * @return array<string, mixed> Datos de metadatos listos para renderizar.
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'keywords' => implode(', ', $this->keywords),
            'canonicalUrl' => $this->canonicalUrl,
            'image' => $this->image,
        ];
    }
}


