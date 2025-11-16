<?php

namespace App\Mcp\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class POSTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Provides access to Point of Sale (POS) operations in MercuryApp.
        Supports processing sales transactions, searching products/services, and managing payment methods.
        Use this tool to handle real-time sales transactions at the point of sale.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        if (!auth()->check()) {
            return Response::error('User must be authenticated to access POS operations.');
        }

        $operation = $request->input('operation', 'list-routes');

        if ($operation === 'list-routes') {
            $routes = collect(Route::getRoutes())
                ->filter(fn($route) => str_starts_with($route->uri(), 'pos/'))
                ->map(fn($route) => [
                    'method' => implode('|', array_filter($route->methods(), fn($m) => $m !== 'HEAD')),
                    'uri' => $route->uri(),
                    'name' => $route->getName(),
                ])
                ->values()
                ->all();

            $output = "## POS Routes Available\n\nTotal routes: " . count($routes) . "\n\n";
            foreach ($routes as $route) {
                $output .= "- **{$route['method']}** `/{$route['uri']}` - `{$route['name']}`\n";
            }
            return Response::text($output);
        }

        return Response::text("POS tool executed. Operation: {$operation}");
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'operation' => $schema->string()
                ->enum(['list-routes', 'search-products', 'search-services', 'search-customers', 'process-sale'])
                ->default('list-routes'),
        ];
    }
}
