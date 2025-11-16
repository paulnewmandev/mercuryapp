<?php

namespace App\Mcp\Tools\Chatbot;

use App\Models\Customer;
use App\Models\Product;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class TableGeneratorTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Genera una tabla HTML con datos del sistema. Puede crear tablas de clientes, productos, órdenes, facturas, etc.
        La tabla se formatea en HTML para ser mostrada en el chat.
        Sinónimos: crear tabla, generar tabla, tabla HTML, mostrar en tabla, listar en tabla.
        Ejemplos: "crea una tabla con los últimos 10 clientes", "genera una tabla con productos Apple iPhone".
    MARKDOWN;

    public function handle(Request $request): Response
    {
        if (!Auth::check()) {
            return Response::error('Usuario debe estar autenticado');
        }

        $arguments = $request->all();
        $dataType = $arguments['data_type'] ?? 'customers';
        $limit = $arguments['limit'] ?? 10;
        $filters = $arguments['filters'] ?? [];

        $user = Auth::user();
        $companyId = $user->company_id;

        if (!$companyId) {
            $companyId = \App\Models\Company::query()->first()?->id;
        }

        if (!$companyId) {
            return Response::error('No se encontró una compañía asociada');
        }

        $html = $this->generateTable($dataType, $companyId, $limit, $filters);

        return Response::text($html);
    }

    private function generateTable(string $dataType, string $companyId, int $limit, array $filters): string
    {
        $output = "## Tabla de Datos\n\n";
        $output .= "<div class=\"overflow-x-auto\">\n";
        $output .= "<table class=\"min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-300 dark:border-gray-600\">\n";
        $output .= "<thead class=\"bg-gray-50 dark:bg-slate-800\">\n";
        $output .= "<tr>\n";

        $data = [];
        $headers = [];

        switch ($dataType) {
            case 'customers':
                $headers = ['ID', 'Nombre', 'Documento', 'Email', 'Teléfono', 'Estado'];
                $query = Customer::where('company_id', $companyId);
                
                if (isset($filters['customer_type'])) {
                    $query->where('customer_type', $filters['customer_type']);
                }
                
                $customers = $query->latest('created_at')->limit($limit)->get();
                
                if ($customers->isEmpty()) {
                    return '<div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg"><p class="text-yellow-800 dark:text-yellow-200">No se encontraron clientes para mostrar en la tabla.</p></div>';
                }
                
                foreach ($customers as $customer) {
                    $data[] = [
                        htmlspecialchars(substr($customer->id, 0, 8) . '...'),
                        htmlspecialchars($customer->display_name ?? 'Sin nombre'),
                        htmlspecialchars(($customer->document_type ?? 'N/A') . ' - ' . ($customer->document_number ?? 'N/A')),
                        htmlspecialchars($customer->email ?? 'N/A'),
                        htmlspecialchars($customer->phone_number ?? 'N/A'),
                        $customer->status === 'A' ? 'Activo' : 'Inactivo',
                    ];
                }
                break;

            case 'products':
                $headers = ['ID', 'Nombre', 'SKU', 'Stock', 'Precio', 'Categoría', 'Línea'];
                $query = Product::where('company_id', $companyId)->where('status', 'A');
                
                if (isset($filters['brand'])) {
                    $brandName = $filters['brand'];
                    $query->whereHas('line', function($q) use ($brandName) {
                        $q->where('name', 'like', "%{$brandName}%");
                    });
                }
                
                if (isset($filters['name'])) {
                    $query->where('name', 'like', "%{$filters['name']}%");
                }
                
                // Si se busca "apple" o "iphone", buscar en nombre y línea
                if (isset($filters['search'])) {
                    $search = strtolower($filters['search']);
                    if (strpos($search, 'apple') !== false || strpos($search, 'iphone') !== false) {
                        $query->where(function($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                              ->orWhereHas('line', function($lineQ) use ($search) {
                                  $lineQ->where('name', 'like', "%{$search}%");
                              });
                        });
                    }
                }
                
                $products = $query->with(['category', 'line'])->limit($limit)->get();
                
                foreach ($products as $product) {
                    $price = $this->getProductPrice($product);
                    $data[] = [
                        substr($product->id, 0, 8) . '...',
                        $product->name,
                        $product->sku ?? 'N/A',
                        $product->stock ?? 0,
                        $price ? 'USD ' . number_format($price, 2) : 'N/A',
                        $product->category?->name ?? 'N/A',
                        $product->line?->name ?? 'N/A',
                    ];
                }
                break;

            case 'orders':
                $headers = ['Número', 'Cliente', 'Estado', 'Total', 'Abono', 'Restante', 'Fecha'];
                $query = \App\Models\WorkshopOrder::where('company_id', $companyId);
                
                if (isset($filters['state'])) {
                    $query->whereHas('state', function($q) use ($filters) {
                        $q->where('name', 'like', "%{$filters['state']}%");
                    });
                }
                
                $orders = $query->with(['customer', 'state'])
                    ->latest('created_at')
                    ->limit($limit)
                    ->get();
                
                foreach ($orders as $order) {
                    $data[] = [
                        $order->order_number,
                        $order->customer?->display_name ?? 'N/A',
                        $order->state?->name ?? 'Sin estado',
                        'USD ' . number_format($order->total_cost ?? 0, 2),
                        'USD ' . number_format($order->advance_amount ?? 0, 2),
                        'USD ' . number_format($order->balance ?? 0, 2),
                        $order->created_at->format('d/m/Y'),
                    ];
                }
                break;

            case 'invoices':
                $headers = ['Número', 'Cliente', 'Total', 'Pagado', 'Pendiente', 'Fecha', 'Estado'];
                $query = \App\Models\Invoice::where('company_id', $companyId);
                
                if (isset($filters['only_pending']) && $filters['only_pending']) {
                    $query->whereColumn('total_amount', '>', 'total_paid');
                }
                
                $invoices = $query->with(['customer'])
                    ->latest('issue_date')
                    ->limit($limit)
                    ->get();
                
                foreach ($invoices as $invoice) {
                    $balance = $invoice->total_amount - $invoice->total_paid;
                    $data[] = [
                        $invoice->invoice_number,
                        $invoice->customer?->display_name ?? 'N/A',
                        'USD ' . number_format($invoice->total_amount, 2),
                        'USD ' . number_format($invoice->total_paid, 2),
                        'USD ' . number_format($balance, 2),
                        $invoice->issue_date->format('d/m/Y'),
                        $balance > 0 ? 'Pendiente' : 'Pagada',
                    ];
                }
                break;

            default:
                return "Tipo de datos no soportado: {$dataType}";
        }

        // Generar headers
        foreach ($headers as $header) {
            $output .= "<th class=\"px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300\">{$header}</th>\n";
        }
        $output .= "</tr>\n";
        $output .= "</thead>\n";
        $output .= "<tbody class=\"bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-gray-700\">\n";

        // Generar filas
        if (empty($data)) {
            $output .= "<tr><td colspan=\"" . count($headers) . "\" class=\"px-4 py-3 text-sm text-center text-gray-500 dark:text-gray-400\">No hay datos para mostrar</td></tr>\n";
        } else {
            foreach ($data as $row) {
                $output .= "<tr class=\"hover:bg-gray-50 dark:hover:bg-slate-800\">\n";
                foreach ($row as $cell) {
                    $output .= "<td class=\"px-4 py-3 text-sm text-gray-900 dark:text-gray-100\">{$cell}</td>\n";
                }
                $output .= "</tr>\n";
            }
        }

        $output .= "</tbody>\n";
        $output .= "</table>\n";
        $output .= "</div>\n";

        return $output;
    }

    private function getProductPrice($product): ?float
    {
        if ($product->price_list_pos_id) {
            $itemPrice = \App\Models\ItemPrice::where('price_list_id', $product->price_list_pos_id)
                ->where('product_id', $product->id)
                ->first();
            
            return $itemPrice?->price ?? null;
        }

        return null;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'data_type' => $schema->string()
                ->enum(['customers', 'products', 'orders', 'invoices'])
                ->default('customers')
                ->description('Tipo de datos a mostrar: customers, products, orders, invoices'),
            'limit' => $schema->integer()
                ->default(10)
                ->minimum(1)
                ->maximum(100)
                ->description('Número máximo de registros a mostrar'),
            'filters' => $schema->object()
                ->nullable()
                ->description('Filtros opcionales: para products puede incluir brand (marca), name (nombre), search (búsqueda general). Para orders puede incluir state (estado). Para invoices puede incluir only_pending (boolean).'),
        ];
    }
}

