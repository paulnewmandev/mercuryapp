<?php

namespace App\Mcp\Tools\Chatbot;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\ReceivableEntry;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CustomerDebtQueryTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Consulta las deudas totales de un cliente. Incluye:
        - Facturas pendientes de pago (saldo pendiente)
        - Cuentas por cobrar pendientes
        - Total de deuda del cliente
        Puede buscar por documento, email o nombre del cliente.
        Útil para consultas como: "¿Cuánto debe el cliente 1759474057?" o "¿Cuánto debe Juan Pérez?"
    MARKDOWN;

    public function handle(Request $request): Response
    {
        if (!Auth::check()) {
            return Response::error('Usuario debe estar autenticado');
        }

        $arguments = $request->all();

        $user = Auth::user();
        $companyId = $user->company_id;

        if (!$companyId) {
            $companyId = \App\Models\Company::query()->first()?->id;
        }

        if (!$companyId) {
            return Response::error('No se encontró una compañía asociada');
        }

        $documentNumber = $arguments['document_number'] ?? null;
        $email = $arguments['email'] ?? null;
        $customerId = $arguments['customer_id'] ?? null;

        if (!$documentNumber && !$email && !$customerId) {
            return Response::error('Debe proporcionar document_number, email o customer_id');
        }

        // Buscar cliente
        $query = Customer::where('company_id', $companyId);

        if ($customerId) {
            $query->where('id', $customerId);
        } elseif ($documentNumber) {
            $query->where('document_number', $documentNumber);
        } elseif ($email) {
            $query->where('email', $email);
        }

        $customer = $query->first();

        if (!$customer) {
            return Response::text("No se encontró un cliente con los criterios proporcionados.");
        }

        // Calcular deuda en facturas
        $invoicesQuery = Invoice::where('company_id', $companyId)
            ->where('customer_id', $customer->id)
            ->whereColumn('total_amount', '>', 'total_paid');

        $invoicesTotal = $invoicesQuery->sum(DB::raw('total_amount - total_paid'));
        $invoicesCount = $invoicesQuery->count();

        // Calcular cuentas por cobrar pendientes
        $receivablesTotal = ReceivableEntry::where('company_id', $companyId)
            ->where('is_collected', false)
            ->whereHas('category', function($q) use ($customer) {
                // Asumiendo que las cuentas por cobrar pueden estar relacionadas con el cliente
                // Ajustar según la estructura real de tu base de datos
            })
            ->sum('amount_cents') / 100;

        // Por ahora, buscaremos receivables que tengan referencia al cliente en el concepto o descripción
        // Esto puede necesitar ajuste según tu modelo de datos
        $receivablesWithCustomer = ReceivableEntry::where('company_id', $companyId)
            ->where('is_collected', false)
            ->where(function($q) use ($customer) {
                $q->where('concept', 'like', "%{$customer->document_number}%")
                  ->orWhere('description', 'like', "%{$customer->document_number}%")
                  ->orWhere('reference', 'like', "%{$customer->document_number}%");
            })
            ->sum('amount_cents') / 100;

        $totalDebt = $invoicesTotal + $receivablesWithCustomer;

        $output = "## Deudas del Cliente\n\n";
        $output .= "**Cliente:** {$customer->display_name}\n";
        $output .= "**Documento:** {$customer->document_type} - {$customer->document_number}\n";
        if ($customer->email) {
            $output .= "**Email:** {$customer->email}\n";
        }
        $output .= "\n";

        $output .= "### Facturas Pendientes\n";
        $output .= "- **Cantidad:** {$invoicesCount} factura(s)\n";
        $output .= "- **Total pendiente:** USD " . number_format($invoicesTotal, 2) . "\n\n";

        if ($receivablesWithCustomer > 0) {
            $output .= "### Cuentas por Cobrar Pendientes\n";
            $output .= "- **Total pendiente:** USD " . number_format($receivablesWithCustomer, 2) . "\n\n";
        }

        $output .= "### **Total de Deuda: USD " . number_format($totalDebt, 2) . "**\n";

        // Listar facturas pendientes
        if ($invoicesCount > 0) {
            $invoices = $invoicesQuery->with(['customer'])->limit(10)->get();
            $output .= "\n### Detalle de Facturas Pendientes\n";
            foreach ($invoices as $invoice) {
                $balance = $invoice->total_amount - $invoice->total_paid;
                $output .= "- **Factura {$invoice->invoice_number}**\n";
                $output .= "  - Fecha: {$invoice->issue_date->format('d/m/Y')}\n";
                $output .= "  - Total: USD " . number_format($invoice->total_amount, 2) . "\n";
                $output .= "  - Pagado: USD " . number_format($invoice->total_paid, 2) . "\n";
                $output .= "  - Pendiente: USD " . number_format($balance, 2) . "\n";
                if ($invoice->due_date) {
                    $output .= "  - Vencimiento: {$invoice->due_date->format('d/m/Y')}\n";
                }
                $output .= "\n";
            }
        }

        return Response::text($output);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'document_number' => $schema->string()
                ->nullable()
                ->description('Número de documento del cliente'),
            'email' => $schema->string()
                ->nullable()
                ->description('Email del cliente'),
            'customer_id' => $schema->string()
                ->nullable()
                ->description('ID del cliente'),
        ];
    }
}

