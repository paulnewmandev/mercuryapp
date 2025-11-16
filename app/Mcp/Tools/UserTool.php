<?php

namespace App\Mcp\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class UserTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Provides access to user profile operations in MercuryApp.
        Supports managing user profile data, avatar, and password changes.
        Use this tool to access and update user profile information.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        if (!auth()->check()) {
            return Response::error('User must be authenticated to access user profile.');
        }

        $operation = $request->input('operation', 'list-routes');

        if ($operation === 'list-routes') {
            $routes = collect(Route::getRoutes())
                ->filter(fn($route) => str_starts_with($route->uri(), 'profile/') || $route->getName() === 'profile.show')
                ->map(fn($route) => [
                    'method' => implode('|', array_filter($route->methods(), fn($m) => $m !== 'HEAD')),
                    'uri' => $route->uri(),
                    'name' => $route->getName(),
                ])
                ->values()
                ->all();

            $output = "## User Profile Routes Available\n\nTotal routes: " . count($routes) . "\n\n";
            foreach ($routes as $route) {
                $output .= "- **{$route['method']}** `/{$route['uri']}` - `{$route['name']}`\n";
            }
            return Response::text($output);
        }

        return Response::text("User tool executed. Operation: {$operation}");
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'operation' => $schema->string()
                ->enum(['list-routes', 'get-profile', 'update-profile', 'update-password'])
                ->default('list-routes'),
        ];
    }
}
