<?php

namespace App\Mcp\Tools\Chatbot;

use App\Models\Provider;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ProviderQueryTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Busca proveedores por nombre, documento, email o teléfono.
        Retorna información completa del proveedor.
        Útil para consultas como: "¿Tenemos un proveedor llamado ABC?" o "Busca el proveedor con RUC 123456789"
    MARKDOWN;

    public function handle(Request $request): Response
    {
        if (!Auth::check()) {
            return Response::error('Usuario debe estar autenticado');
        }

        $providerName = $request->input('provider_name');
        $documentNumber = $request->input('document_number');
        $email = $request->input('email');
        $phone = $request->input('phone_number');

        if (!$providerName && !$documentNumber && !$email && !$phone) {
            return Response::error('Debe proporcionar al menos uno de: provider_name, document_number, email, phone_number');
        }

        $user = Auth::user();
        $companyId = $user->company_id;

        if (!$companyId) {
            $companyId = \App\Models\Company::query()->first()?->id;
        }

        if (!$companyId) {
            return Response::error('No se encontró una compañía asociada');
        }

        $query = Provider::where('company_id', $companyId);

        if ($providerName) {
            $query->where(function($q) use ($providerName) {
                $q->where('name', 'like', "%{$providerName}%")
                  ->orWhere('business_name', 'like', "%{$providerName}%");
            });
        }
        if ($documentNumber) {
            $query->where('document_number', $documentNumber);
        }
        if ($email) {
            $query->where('email', $email);
        }
        if ($phone) {
            $query->where('phone_number', $phone);
        }

        $providers = $query->limit(10)->get();

        if ($providers->isEmpty()) {
            return Response::text("No se encontraron proveedores con los criterios proporcionados.");
        }

        $output = "## Proveedor(es) Encontrado(s)\n\n";
        
        foreach ($providers as $provider) {
            $output .= "### {$provider->name}\n";
            if ($provider->business_name) {
                $output .= "**Razón Social:** {$provider->business_name}\n";
            }
            $output .= "**Documento:** {$provider->document_type} - {$provider->document_number}\n";
            $output .= "**Email:** {$provider->email ?? 'N/A'}\n";
            $output .= "**Teléfono:** {$provider->phone_number ?? 'N/A'}\n";
            $output .= "**Dirección:** {$provider->address ?? 'N/A'}\n";
            $output .= "**Estado:** " . ($provider->status === 'A' ? 'Activo' : 'Inactivo') . "\n";
            $output .= "\n---\n\n";
        }

        return Response::text($output);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'provider_name' => $schema->string()
                ->nullable()
                ->description('Nombre del proveedor'),
            'document_number' => $schema->string()
                ->nullable()
                ->description('Número de documento del proveedor'),
            'email' => $schema->string()
                ->nullable()
                ->description('Email del proveedor'),
            'phone_number' => $schema->string()
                ->nullable()
                ->description('Teléfono del proveedor'),
        ];
    }
}

