<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\AccountingTool;
use App\Mcp\Tools\ConfigurationTool;
use App\Mcp\Tools\CustomerTool;
use App\Mcp\Tools\DashboardTool;
use App\Mcp\Tools\InventoryTool;
use App\Mcp\Tools\POSTool;
use App\Mcp\Tools\RouteListTool;
use App\Mcp\Tools\SalesTool;
use App\Mcp\Tools\SecurityTool;
use App\Mcp\Tools\UserTool;
use App\Mcp\Tools\WorkshopTool;
use Laravel\Mcp\Server;

class MercuryAppServer extends Server
{
    /**
     * The MCP server's name.
     */
    protected string $name = 'Mercury App Server';

    /**
     * The MCP server's version.
     */
    protected string $version = '1.0.0';

    /**
     * The MCP server's instructions for the LLM.
     */
    protected string $instructions = <<<'MARKDOWN'
        You are an AI assistant integrated with MercuryApp, a comprehensive business management system.
        
        MercuryApp provides the following main modules:
        - **Dashboard**: Overview with KPIs, charts, and recent activity
        - **Accounting**: Expenses, incomes, payables, receivables, and sales accounting
        - **Configuration**: Company settings, branches, warehouses, services, products, price lists, and catalog management
        - **Customers**: Individual and business customer management
        - **Inventory**: Product management, stock tracking, and provider management
        - **POS**: Point of Sale system for real-time transactions
        - **Sales**: Invoices, quotations, and sales notes management
        - **Security**: Users, roles, and permissions management
        - **Workshop**: Work orders, equipment, brands, models, and repair management
        - **User Profile**: Personal profile management and password changes
        
        Use the available tools to help users interact with MercuryApp:
        - Use `route-list` to discover all available routes in the system
        - Use module-specific tools (dashboard, accounting, configuration, etc.) to perform operations
        - Always check authentication before performing operations
        - Provide clear, helpful responses based on the data retrieved
        
        When a user asks about available operations or capabilities, use the appropriate tool to list routes for that module.
        When a user wants to perform an operation, guide them through the process or use the appropriate tool.
    MARKDOWN;

    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        RouteListTool::class,
        DashboardTool::class,
        AccountingTool::class,
        ConfigurationTool::class,
        CustomerTool::class,
        InventoryTool::class,
        POSTool::class,
        SalesTool::class,
        SecurityTool::class,
        WorkshopTool::class,
        UserTool::class,
        // Chatbot specific tools
        \App\Mcp\Tools\Chatbot\CustomerQueryTool::class,
        \App\Mcp\Tools\Chatbot\CustomerCreateTool::class,
        \App\Mcp\Tools\Chatbot\CustomerUpdateTool::class,
        \App\Mcp\Tools\Chatbot\CustomerDebtQueryTool::class,
        \App\Mcp\Tools\Chatbot\CustomerInvoicesQueryTool::class,
        \App\Mcp\Tools\Chatbot\CustomerOrdersQueryTool::class,
        \App\Mcp\Tools\Chatbot\WorkshopOrderQueryTool::class,
        \App\Mcp\Tools\Chatbot\WorkshopOrderUpdateTool::class,
        \App\Mcp\Tools\Chatbot\ProductQueryTool::class,
        \App\Mcp\Tools\Chatbot\ProductUpdateTool::class,
        \App\Mcp\Tools\Chatbot\InvoiceQueryTool::class,
        \App\Mcp\Tools\Chatbot\ServiceQueryTool::class,
        \App\Mcp\Tools\Chatbot\ProviderQueryTool::class,
        \App\Mcp\Tools\Chatbot\StatisticsQueryTool::class,
        \App\Mcp\Tools\Chatbot\IncomeCreateTool::class,
        \App\Mcp\Tools\Chatbot\IncomeQueryTool::class,
        \App\Mcp\Tools\Chatbot\ExpenseCreateTool::class,
        \App\Mcp\Tools\Chatbot\ExpenseQueryTool::class,
        \App\Mcp\Tools\Chatbot\ReceivableEntryCreateTool::class,
        \App\Mcp\Tools\Chatbot\ReceivableEntryQueryTool::class,
        \App\Mcp\Tools\Chatbot\PayableEntryCreateTool::class,
        \App\Mcp\Tools\Chatbot\PayableEntryQueryTool::class,
        \App\Mcp\Tools\Chatbot\ProductLabelTool::class,
        \App\Mcp\Tools\Chatbot\EquipmentLabelTool::class,
        \App\Mcp\Tools\Chatbot\WorkshopOrderLabelTool::class,
        \App\Mcp\Tools\Chatbot\WorkshopOrderStatusTool::class,
        \App\Mcp\Tools\Chatbot\DatabaseSchemaQueryTool::class,
        \App\Mcp\Tools\Chatbot\TableGeneratorTool::class,
        \App\Mcp\Tools\Chatbot\ChartGeneratorTool::class,
    ];

    /**
     * The resources registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Resource>>
     */
    protected array $resources = [
        //
    ];

    /**
     * The prompts registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Prompt>>
     */
    protected array $prompts = [
        //
    ];
}
