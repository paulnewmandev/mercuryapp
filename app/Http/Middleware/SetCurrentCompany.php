<?php

namespace App\Http\Middleware;

use App\Support\Tenant\CurrentCompany;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Middleware encargado de inyectar el contexto de compañía para cada petición autenticada.
 */
class SetCurrentCompany
{
    /**
     * @param CurrentCompany $currentCompany Gestor del contexto multi-tenant.
     */
    public function __construct(private readonly CurrentCompany $currentCompany)
    {
    }

    /**
     * Establece el identificador de compañía antes de continuar la petición.
     *
     * @param Request $request
     * @param Closure(Request):mixed $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        // Si el usuario es super admin (sin company_id), usar la primera compañía disponible
        if (! $companyId && $user) {
            $company = \App\Models\Company::query()->first();
            $companyId = $company?->id;
        }

        $this->currentCompany->set($companyId);

        $response = $next($request);

        if (! Auth::check()) {
            $this->currentCompany->clear();
        }

        return $response;
    }
}

