<?php

namespace App\Support\Tenant;

/**
 * Gestor de contexto multi-tenant basado en la compañía actual.
 *
 * Permite establecer y recuperar el identificador de la compañía en curso,
 * simplificando la aplicación de filtros en modelos y servicios.
 */
class CurrentCompany
{
    /**
     * Identificador de la compañía activa.
     *
     * @var string|null
     */
    private ?string $companyId = null;

    /**
     * Establece el identificador de la compañía actual.
     *
     * @param string|null $companyId
     *
     * @return void
     */
    public function set(?string $companyId): void
    {
        $this->companyId = $companyId ?: null;
    }

    /**
     * Limpia el contexto de compañía.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->companyId = null;
    }

    /**
     * Indica si existe una compañía activa en el contexto.
     *
     * @return bool
     */
    public function has(): bool
    {
        return filled($this->companyId);
    }

    /**
     * Obtiene el identificador de la compañía activa.
     *
     * @return string|null
     */
    public function id(): ?string
    {
        return $this->companyId;
    }
}

