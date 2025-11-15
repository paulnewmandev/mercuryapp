<?php

namespace App\Contracts;

use App\DataTransferObjects\SeoMetaData;

/**
 * Contrato que define las operaciones necesarias para gestionar metadatos SEO.
 *
 * @package App\Contracts
 */
interface SeoMetaManagerContract
{
    /**
    * Genera metadatos combinando valores predeterminados con datos específicos.
    *
    * @param array<string, mixed> $overrides Valores a sobrescribir sobre los predeterminados.
    *
    * @return SeoMetaData Objeto de transferencia de metadatos.
    */
    public function compose(array $overrides = []): SeoMetaData;
}


