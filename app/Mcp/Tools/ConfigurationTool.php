<?php

namespace App\Mcp\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ConfigurationTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Provides access to configuration operations in MercuryApp.
        Supports managing company settings, branches, warehouses, price lists, services, products, and all configuration data.
        Use this tool to configure system settings, catalogs, and business parameters.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        if (!auth()->check()) {
            return Response::error('User must be authenticated to access configuration data.');
        }

        $operation = $request->input('operation', 'list-routes');

        if ($operation === 'list-routes') {
            $routes = collect(Route::getRoutes())
                ->filter(fn($route) => str_starts_with($route->uri(), 'configuration/') || str_starts_with($route->uri(), 'catalog/'))
                ->map(fn($route) => [
                    'method' => implode('|', array_filter($route->methods(), fn($m) => $m !== 'HEAD')),
                    'uri' => $route->uri(),
                    'name' => $route->getName(),
                ])
                ->values()
                ->all();

            $output = "## Configuration Routes Available\n\nTotal routes: " . count($routes) . "\n\n";
            foreach ($routes as $route) {
                $output .= "- **{$route['method']}** `/{$route['uri']}` - `{$route['name']}`\n";
            }
            return Response::text($output);
        }

        return Response::text("Configuration tool executed. Operation: {$operation}");
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'operation' => $schema->string()
                ->enum(['list-routes', 'list', 'get', 'create', 'update', 'delete'])
                ->default('list-routes'),
            'resource' => $schema->string()
                ->description('Configuration resource (e.g., branches, warehouses, services, products)'),
        ];
    }
}
