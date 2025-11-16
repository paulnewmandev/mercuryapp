<?php

namespace App\Mcp\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class WorkshopTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Provides access to workshop operations in MercuryApp.
        Supports work orders, brands, categories, models, states, equipment, accessories, and advances.
        Use this tool to manage workshop-related data including repair orders and equipment tracking.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        if (!auth()->check()) {
            return Response::error('User must be authenticated to access workshop data.');
        }

        $operation = $request->input('operation', 'list-routes');
        $resource = $request->input('resource'); // work-orders, brands, categories, models, states, equipment, accessories, advances
        $action = $request->input('action', 'list'); // list, get, create, update, delete, status

        if ($operation === 'list-routes') {
            return $this->listRoutes();
        }

        return Response::text("Workshop tool executed. Resource: {$resource}, Action: {$action}. Implement controller call here.");
    }

    private function listRoutes(): Response
    {
        $workshopRoutes = collect(Route::getRoutes())
            ->filter(fn($route) => str_starts_with($route->uri(), 'workshop/'))
            ->map(function ($route) {
                return [
                    'method' => implode('|', array_filter($route->methods(), fn($m) => $m !== 'HEAD')),
                    'uri' => $route->uri(),
                    'name' => $route->getName(),
                ];
            })
            ->values()
            ->all();

        $output = "## Workshop Routes Available\n\n";
        $output .= "Total routes: " . count($workshopRoutes) . "\n\n";

        foreach ($workshopRoutes as $route) {
            $output .= "- **{$route['method']}** `/{$route['uri']}` - `{$route['name']}`\n";
        }

        return Response::text($output);
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'operation' => $schema->string()
                ->enum(['list-routes', 'execute'])
                ->default('list-routes')
                ->description('Operation to perform'),
            'resource' => $schema->string()
                ->enum(['work-orders', 'brands', 'categories', 'models', 'states', 'equipment', 'accessories', 'advances'])
                ->description('The workshop resource to operate on'),
            'action' => $schema->string()
                ->enum(['list', 'get', 'create', 'update', 'delete', 'status'])
                ->default('list')
                ->description('The action to perform'),
            'id' => $schema->string()
                ->description('Required for get, update, delete operations'),
            'data' => $schema->object()
                ->description('Data for create/update operations'),
        ];
    }
}
