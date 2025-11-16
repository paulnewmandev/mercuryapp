<?php

namespace App\Mcp\Tools\Chatbot;

use App\Models\Service;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ServiceQueryTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Busca servicios por nombre, categoría o ID.
        Retorna información de precio, categoría y disponibilidad.
        Útil para consultas como: "¿Qué servicios tenemos?" o "¿Cuánto cuesta el servicio de reparación?"
    MARKDOWN;

    public function handle(Request $request): Response
    {
        if (!Auth::check()) {
            return Response::error('Usuario debe estar autenticado');
        }

        $serviceName = $request->input('service_name');
        $categoryName = $request->input('category_name');
        $serviceId = $request->input('service_id');

        if (!$serviceName && !$categoryName && !$serviceId) {
            return Response::error('Debe proporcionar service_name, category_name o service_id');
        }

        $user = Auth::user();
        $companyId = $user->company_id;

        if (!$companyId) {
            $companyId = \App\Models\Company::query()->first()?->id;
        }

        if (!$companyId) {
            return Response::error('No se encontró una compañía asociada');
        }

        $query = Service::where('company_id', $companyId)->where('status', 'A');

        if ($serviceName) {
            $query->where('name', 'like', "%{$serviceName}%");
        }
        if ($serviceId) {
            $query->where('id', $serviceId);
        }
        if ($categoryName) {
            $query->whereHas('category', function($q) use ($categoryName) {
                $q->where('name', 'like', "%{$categoryName}%");
            });
        }

        $services = $query->with(['category'])
            ->limit(20)
            ->get();

        if ($services->isEmpty()) {
            return Response::text("No se encontraron servicios con los criterios proporcionados.");
        }

        $output = "## Servicio(s) Encontrado(s)\n\n";
        
        foreach ($services as $service) {
            $output .= "### {$service->name}\n";
            if ($service->category) {
                $output .= "**Categoría:** {$service->category->name}\n";
            }
            if ($service->price) {
                $output .= "**Precio:** USD " . number_format($service->price, 2) . "\n";
            }
            if ($service->description) {
                $output .= "**Descripción:** {$service->description}\n";
            }
            $output .= "\n---\n\n";
        }

        return Response::text($output);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'service_name' => $schema->string()
                ->nullable()
                ->description('Nombre del servicio a buscar'),
            'category_name' => $schema->string()
                ->nullable()
                ->description('Nombre de la categoría para buscar todos sus servicios'),
            'service_id' => $schema->string()
                ->nullable()
                ->description('ID del servicio'),
        ];
    }
}

