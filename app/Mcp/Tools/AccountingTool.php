<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\Accounting\ExpenseController;
use App\Http\Controllers\Accounting\IncomeController;
use App\Http\Controllers\Accounting\PayableEntryController;
use App\Http\Controllers\Accounting\ReceivableEntryController;
use App\Http\Controllers\Accounting\AccountingSalesController;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class AccountingTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Provides access to accounting operations in MercuryApp.
        Supports operations on expenses, incomes, payables, receivables, and sales data.
        Operations: list, get, create, update, delete, status, settlement (for payables/receivables).
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        if (!Auth::check()) {
            return Response::error('User must be authenticated to access accounting data.');
        }

        $resource = $request->input('resource'); // expenses, incomes, payables, receivables, sales
        $operation = $request->input('operation', 'list'); // list, get, create, update, delete, status, settlement
        $id = $request->input('id');
        $data = $request->input('data', []);

        try {
            // Map resource to controller
            $controllerMap = [
                'expenses' => ExpenseController::class,
                'incomes' => IncomeController::class,
                'payables' => PayableEntryController::class,
                'receivables' => ReceivableEntryController::class,
                'sales' => AccountingSalesController::class,
            ];

            if (!isset($controllerMap[$resource])) {
                return Response::error("Invalid resource: {$resource}. Valid resources: " . implode(', ', array_keys($controllerMap)));
            }

            $controllerClass = $controllerMap[$resource];
            $controller = app($controllerClass);
            
            // Create request object with data
            $httpRequest = request();
            if (!empty($data)) {
                $httpRequest->merge($data);
            }
            if ($id) {
                $httpRequest->route()->setParameter($resource === 'expenses' ? 'expense' : ($resource === 'incomes' ? 'income' : ($resource === 'payables' ? 'payable' : ($resource === 'receivables' ? 'receivable' : 'invoice'))), $id);
            }

            // Return route information for now (can be extended to call controllers)
            $routes = collect(\Illuminate\Support\Facades\Route::getRoutes())
                ->filter(fn($route) => str_contains($route->uri(), $resource === 'expenses' ? 'expenses' : ($resource === 'incomes' ? 'incomes' : ($resource === 'payables' ? 'payables' : ($resource === 'receivables' ? 'receivables' : 'sales')))))
                ->map(fn($route) => [
                    'method' => implode('|', array_filter($route->methods(), fn($m) => $m !== 'HEAD')),
                    'uri' => $route->uri(),
                    'name' => $route->getName(),
                ])
                ->values()
                ->all();

            $output = "## Accounting Routes for {$resource}\n\n";
            $output .= "Total routes: " . count($routes) . "\n\n";
            foreach ($routes as $route) {
                $output .= "- **{$route['method']}** `/{$route['uri']}` - `{$route['name']}`\n";
            }

            return Response::text($output);
        } catch (\Exception $e) {
            return Response::error("Error performing accounting operation: " . $e->getMessage());
        }
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'resource' => $schema->string()
                ->enum(['expenses', 'incomes', 'payables', 'receivables', 'sales'])
                ->description('The accounting resource to operate on'),
            'operation' => $schema->string()
                ->enum(['list', 'get', 'create', 'update', 'delete', 'status', 'settlement'])
                ->default('list')
                ->description('The operation to perform'),
            'id' => $schema->string()
                ->description('Required for get, update, delete, status, and settlement operations'),
            'data' => $schema->object()
                ->description('Data for create/update operations (JSON object)'),
        ];
    }
}
