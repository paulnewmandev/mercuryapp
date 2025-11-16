<?php

namespace App\Mcp\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class RouteListTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Lists all available routes in the MercuryApp application. 
        This tool provides a comprehensive overview of all API endpoints, their HTTP methods, names, and URIs.
        Use this to discover what operations are available in the system.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $routes = Route::getRoutes();
        $routeList = [];

        foreach ($routes as $route) {
            $methods = array_filter($route->methods(), fn($method) => $method !== 'HEAD');
            $uri = $route->uri();
            $name = $route->getName();
            $action = $route->getActionName();

            // Skip closure routes and system routes
            if (str_contains($uri, '{path}') || $name === null || str_contains($action, 'Closure')) {
                continue;
            }

            $routeList[] = [
                'method' => implode('|', $methods),
                'uri' => $uri,
                'name' => $name,
                'action' => $action,
            ];
        }

        // Group routes by module
        $groupedRoutes = [];
        foreach ($routeList as $route) {
            $module = $this->extractModule($route['name'] ?? $route['uri']);
            if (!isset($groupedRoutes[$module])) {
                $groupedRoutes[$module] = [];
            }
            $groupedRoutes[$module][] = $route;
        }

        $output = "## Available Routes in MercuryApp\n\n";
        $output .= "Total routes: " . count($routeList) . "\n\n";

        foreach ($groupedRoutes as $module => $routes) {
            $output .= "### " . ucfirst($module) . " (" . count($routes) . " routes)\n\n";
            foreach ($routes as $route) {
                $output .= "- **{$route['method']}** `{$route['uri']}` - `{$route['name']}`\n";
            }
            $output .= "\n";
        }

        return Response::text($output);
    }

    /**
     * Extract module name from route name or URI.
     */
    private function extractModule(string $input): string
    {
        if (str_contains($input, '.')) {
            return explode('.', $input)[0];
        }

        if (str_contains($input, '/')) {
            return explode('/', $input)[0];
        }

        return 'other';
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'module' => $schema->string()
                ->description('Optional: Filter routes by module name (e.g., accounting, workshop, sales)'),
        ];
    }
}
