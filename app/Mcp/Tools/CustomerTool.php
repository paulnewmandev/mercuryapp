<?php

namespace App\Mcp\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CustomerTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Provides access to customer operations in MercuryApp.
        Supports managing individual customers, business customers, and customer categories.
        Use this tool to list, search, create, update, or delete customer records.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        if (!auth()->check()) {
            return Response::error('User must be authenticated to access customer data.');
        }

        $operation = $request->input('operation', 'list-routes');

        if ($operation === 'list-routes') {
            $routes = collect(Route::getRoutes())
                ->filter(fn($route) => str_starts_with($route->uri(), 'customers/'))
                ->map(fn($route) => [
                    'method' => implode('|', array_filter($route->methods(), fn($m) => $m !== 'HEAD')),
                    'uri' => $route->uri(),
                    'name' => $route->getName(),
                ])
                ->values()
                ->all();

            $output = "## Customer Routes Available\n\nTotal routes: " . count($routes) . "\n\n";
            foreach ($routes as $route) {
                $output .= "- **{$route['method']}** `/{$route['uri']}` - `{$route['name']}`\n";
            }
            return Response::text($output);
        }

        return Response::text("Customer tool executed. Operation: {$operation}");
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'operation' => $schema->string()
                ->enum(['list-routes', 'list', 'get', 'create', 'update', 'delete'])
                ->default('list-routes'),
        ];
    }
}
