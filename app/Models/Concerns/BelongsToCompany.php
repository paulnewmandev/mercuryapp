<?php

namespace App\Models\Concerns;

use App\Support\Tenant\CurrentCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait que aplica el contexto multi-tenant basado en la compañía autenticada.
 *
 * Al incluirlo en un modelo que posea la columna `company_id`, todas las consultas
 * se filtran automáticamente por la compañía activa y, al crear registros, se
 * asigna el `company_id` correspondiente si no se proporcionó explícitamente.
 *
 * @mixin Model
 */
trait BelongsToCompany
{
    /**
     * Registra el alcance global y los eventos necesarios para mantener el aislamiento por compañía.
     *
     * @return void
     */
    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('company', function (Builder $builder): void {
            $currentCompany = app(CurrentCompany::class);

            if (! $currentCompany->has()) {
                return;
            }

            $builder->where(
                $builder->qualifyColumn('company_id'),
                $currentCompany->id()
            );
        });

        static::creating(function (Model $model): void {
            if ($model->getAttribute('company_id')) {
                return;
            }

            $currentCompany = app(CurrentCompany::class);
            if ($currentCompany->has()) {
                $model->setAttribute('company_id', $currentCompany->id());
            } else {
                // Si no hay company en el contexto (super admin), usar la primera compañía disponible
                $company = \App\Models\Company::query()->first();
                if ($company) {
                    $model->setAttribute('company_id', $company->id);
                }
            }
        });
    }

    /**
     * Permite filtrar los resultados manualmente por una compañía específica.
     *
     * @param Builder $builder
     * @param string|null $companyId
     *
     * @return Builder
     */
    public function scopeForCompany(Builder $builder, ?string $companyId = null): Builder
    {
        $companyId ??= app(CurrentCompany::class)->id();

        if (! $companyId) {
            return $builder;
        }

        return $builder
            ->withoutGlobalScope('company')
            ->where($builder->qualifyColumn('company_id'), $companyId);
    }
}

