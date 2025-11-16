<?php

namespace App\Mcp\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class SecurityTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Provides access to security operations in MercuryApp.
        Supports managing users, roles, and permissions.
        Use this tool to handle user management, role assignments, and permission configuration.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        if (!auth()->check()) {
            return Response::error('User must be authenticated to access security data.');
        }

        $operation = $request->input('operation', 'list-routes');

        if ($operation === 'list-routes') {
            $routes = collect(Route::getRoutes())
                ->filter(fn($route) => str_starts_with($route->uri(), 'security/'))
                ->map(fn($route) => [
                    'method' => implode('|', array_filter($route->methods(), fn($m) => $m !== 'HEAD')),
                    'uri' => $route->uri(),
                    'name' => $route->getName(),
                ])
                ->values()
                ->all();

            $output = "## Security Routes Available\n\nTotal routes: " . count($routes) . "\n\n";
            foreach ($routes as $route) {
                $output .= "- **{$route['method']}** `/{$route['uri']}` - `{$route['name']}`\n";
            }
            return Response::text($output);
        }

        return Response::text("Security tool executed. Operation: {$operation}");
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'operation' => $schema->string()
                ->enum(['list-routes', 'list-users', 'list-roles', 'list-permissions'])
                ->default('list-routes'),
            'resource' => $schema->string()
                ->enum(['users', 'roles', 'permissions'])
                ->description('The security resource to operate on'),
        ];
    }
}
