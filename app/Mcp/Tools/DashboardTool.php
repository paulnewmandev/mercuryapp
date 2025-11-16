<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\Dashboard\DashboardController;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class DashboardTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Provides access to dashboard data and metrics in MercuryApp.
        Can retrieve KPIs, charts data, and recent activity information.
        Use this to get overview statistics and metrics for the dashboard.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        if (!Auth::check()) {
            return Response::error('User must be authenticated to access dashboard data.');
        }

        $controller = app(DashboardController::class);
        $action = $request->input('action', 'kpis');

        try {
            switch ($action) {
                case 'kpis':
                    $kpis = $controller->kpis(request());
                    return Response::text(json_encode($kpis->getData(), JSON_PRETTY_PRINT));
                
                case 'charts':
                    $charts = $controller->charts(request());
                    return Response::text(json_encode($charts->getData(), JSON_PRETTY_PRINT));
                
                case 'recent-activity':
                    $activity = $controller->recentActivity(request());
                    return Response::text(json_encode($activity->getData(), JSON_PRETTY_PRINT));
                
                default:
                    return Response::error("Invalid action: {$action}. Valid actions are: kpis, charts, recent-activity");
            }
        } catch (\Exception $e) {
            return Response::error("Error retrieving dashboard data: " . $e->getMessage());
        }
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()
                ->enum(['kpis', 'charts', 'recent-activity'])
                ->default('kpis')
                ->description('The dashboard action to perform'),
            'start_date' => $schema->string()
                ->format('date')
                ->description('Optional: Start date for date range filtering (YYYY-MM-DD)'),
            'end_date' => $schema->string()
                ->format('date')
                ->description('Optional: End date for date range filtering (YYYY-MM-DD)'),
        ];
    }
}
