<?php

namespace App\Mcp\Tools\Chatbot;

use App\Models\PayableEntry;
use App\Models\PayableCategory;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class PayableEntryCreateTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Crea una nueva cuenta por pagar en el sistema. Requiere: categoría, fecha, concepto, monto.
        Sinónimos: agregar cuenta por pagar, registrar cuenta por pagar, nueva cuenta por pagar, crear cuenta por pagar, agregar por pagar.
        Ejemplos: "agrega una cuenta por pagar de 400 USD del 25 de enero por factura de proveedor", "registra una cuenta por pagar de 600".
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

        // Validar campos requeridos
        if (empty($arguments['concept']) || empty($arguments['amount']) || empty($arguments['movement_date'])) {
            return Response::text('<div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"><p class="text-red-800 dark:text-red-200">Se requiere: concept (concepto), amount (monto) y movement_date (fecha).</p></div>');
        }

        // Obtener o crear categoría
        $categoryId = $arguments['payable_category_id'] ?? null;
        if (!$categoryId) {
            // Buscar categoría por nombre o usar la primera disponible
            $categoryName = $arguments['category_name'] ?? 'General';
            $category = PayableCategory::where('company_id', $companyId)
                ->where('name', 'like', "%{$categoryName}%")
                ->first();
            
            if (!$category) {
                $category = PayableCategory::where('company_id', $companyId)->first();
            }
            
            if (!$category) {
                return Response::text('<div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"><p class="text-red-800 dark:text-red-200">No se encontró una categoría de cuenta por pagar. Por favor, crea una primero.</p></div>');
            }
            
            $categoryId = $category->id;
        }

        // Convertir monto a centavos
        $amount = (float) $arguments['amount'];
        $amountCents = (int) round($amount * 100);

        // Parsear fecha
        try {
            $movementDate = \Carbon\Carbon::parse($arguments['movement_date']);
        } catch (\Exception $e) {
            return Response::text('<div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"><p class="text-red-800 dark:text-red-200">Fecha inválida. Use el formato YYYY-MM-DD.</p></div>');
        }

        try {
            $payable = PayableEntry::create([
                'company_id' => $companyId,
                'payable_category_id' => $categoryId,
                'movement_date' => $movementDate,
                'concept' => $arguments['concept'],
                'description' => $arguments['description'] ?? null,
                'amount_cents' => $amountCents,
                'currency_code' => $arguments['currency_code'] ?? 'USD',
                'reference' => $arguments['reference'] ?? null,
                'is_paid' => $arguments['is_paid'] ?? false,
            ]);

            $payable->load('category');

            $output = '<div class="space-y-4">';
            $output .= '<div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">';
            $output .= '<p class="text-green-800 dark:text-green-200 font-semibold mb-2">✓ Cuenta por pagar creada correctamente</p>';
            $output .= '</div>';
            
            $output .= '<div class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-2">';
            $output .= '<h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Detalles de la Cuenta por Pagar</h3>';
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[140px]">Categoría:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($payable->category?->name ?? 'N/A') . '</span></div>';
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[140px]">Concepto:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($payable->concept) . '</span></div>';
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[140px]">Monto:</span><span class="text-orange-600 dark:text-orange-400 font-semibold">' . $payable->amount_formatted . '</span></div>';
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[140px]">Fecha:</span><span class="text-gray-900 dark:text-white">' . $payable->movement_date->format('d/m/Y') . '</span></div>';
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[140px]">Estado:</span><span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ' . ($payable->is_paid ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400') . '">' . ($payable->is_paid ? 'Pagada' : 'Pendiente') . '</span></div>';
            
            if ($payable->description) {
                $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[140px]">Descripción:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($payable->description) . '</span></div>';
            }
            
            if ($payable->reference) {
                $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[140px]">Referencia:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($payable->reference) . '</span></div>';
            }
            
            $output .= '</div></div>';

            return Response::text($output);
        } catch (\Exception $e) {
            Log::error("Error al crear cuenta por pagar: " . $e->getMessage());
            return Response::text('<div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"><p class="text-red-800 dark:text-red-200">Error al crear la cuenta por pagar: ' . htmlspecialchars($e->getMessage()) . '</p></div>');
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'payable_category_id' => $schema->string()
                ->nullable()
                ->description('ID de la categoría de cuenta por pagar'),
            'category_name' => $schema->string()
                ->nullable()
                ->description('Nombre de la categoría (si no se proporciona ID, busca por nombre)'),
            'movement_date' => $schema->string()
                ->description('Fecha de la cuenta por pagar (formato: YYYY-MM-DD)'),
            'concept' => $schema->string()
                ->description('Concepto de la cuenta por pagar (ej: "Factura de proveedor", "Servicios pendientes")'),
            'description' => $schema->string()
                ->nullable()
                ->description('Descripción detallada'),
            'amount' => $schema->number()
                ->description('Monto a pagar (ej: 400.50 para $400.50)'),
            'currency_code' => $schema->string()
                ->default('USD')
                ->description('Código de moneda (default: USD)'),
            'reference' => $schema->string()
                ->nullable()
                ->description('Referencia o número de documento relacionado'),
            'is_paid' => $schema->boolean()
                ->default(false)
                ->description('Si ya fue pagada (true/false)'),
        ];
    }
}

