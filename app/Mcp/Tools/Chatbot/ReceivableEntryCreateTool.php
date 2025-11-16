<?php

namespace App\Mcp\Tools\Chatbot;

use App\Models\ReceivableEntry;
use App\Models\ReceivableCategory;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ReceivableEntryCreateTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Crea una nueva cuenta por cobrar en el sistema. Requiere: categoría, fecha, concepto, monto.
        Sinónimos: agregar cuenta por cobrar, registrar cuenta por cobrar, nueva cuenta por cobrar, crear cuenta por cobrar, agregar por cobrar.
        Ejemplos: "agrega una cuenta por cobrar de 300 USD del 20 de enero por servicios pendientes", "registra una cuenta por cobrar de 500".
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
        $categoryId = $arguments['receivable_category_id'] ?? null;
        if (!$categoryId) {
            // Buscar categoría por nombre o usar la primera disponible
            $categoryName = $arguments['category_name'] ?? 'General';
            $category = ReceivableCategory::where('company_id', $companyId)
                ->where('name', 'like', "%{$categoryName}%")
                ->first();
            
            if (!$category) {
                $category = ReceivableCategory::where('company_id', $companyId)->first();
            }
            
            if (!$category) {
                return Response::text('<div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"><p class="text-red-800 dark:text-red-200">No se encontró una categoría de cuenta por cobrar. Por favor, crea una primero.</p></div>');
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
            $receivable = ReceivableEntry::create([
                'company_id' => $companyId,
                'receivable_category_id' => $categoryId,
                'movement_date' => $movementDate,
                'concept' => $arguments['concept'],
                'description' => $arguments['description'] ?? null,
                'amount_cents' => $amountCents,
                'currency_code' => $arguments['currency_code'] ?? 'USD',
                'reference' => $arguments['reference'] ?? null,
                'is_collected' => $arguments['is_collected'] ?? false,
            ]);

            $receivable->load('category');

            $output = '<div class="space-y-4">';
            $output .= '<div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">';
            $output .= '<p class="text-green-800 dark:text-green-200 font-semibold mb-2">✓ Cuenta por cobrar creada correctamente</p>';
            $output .= '</div>';
            
            $output .= '<div class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-2">';
            $output .= '<h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Detalles de la Cuenta por Cobrar</h3>';
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[140px]">Categoría:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($receivable->category?->name ?? 'N/A') . '</span></div>';
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[140px]">Concepto:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($receivable->concept) . '</span></div>';
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[140px]">Monto:</span><span class="text-blue-600 dark:text-blue-400 font-semibold">' . $receivable->amount_formatted . '</span></div>';
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[140px]">Fecha:</span><span class="text-gray-900 dark:text-white">' . $receivable->movement_date->format('d/m/Y') . '</span></div>';
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[140px]">Estado:</span><span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ' . ($receivable->is_collected ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400') . '">' . ($receivable->is_collected ? 'Cobrada' : 'Pendiente') . '</span></div>';
            
            if ($receivable->description) {
                $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[140px]">Descripción:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($receivable->description) . '</span></div>';
            }
            
            if ($receivable->reference) {
                $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[140px]">Referencia:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($receivable->reference) . '</span></div>';
            }
            
            $output .= '</div></div>';

            return Response::text($output);
        } catch (\Exception $e) {
            Log::error("Error al crear cuenta por cobrar: " . $e->getMessage());
            return Response::text('<div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"><p class="text-red-800 dark:text-red-200">Error al crear la cuenta por cobrar: ' . htmlspecialchars($e->getMessage()) . '</p></div>');
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'receivable_category_id' => $schema->string()
                ->nullable()
                ->description('ID de la categoría de cuenta por cobrar'),
            'category_name' => $schema->string()
                ->nullable()
                ->description('Nombre de la categoría (si no se proporciona ID, busca por nombre)'),
            'movement_date' => $schema->string()
                ->description('Fecha de la cuenta por cobrar (formato: YYYY-MM-DD)'),
            'concept' => $schema->string()
                ->description('Concepto de la cuenta por cobrar (ej: "Servicios pendientes", "Factura pendiente")'),
            'description' => $schema->string()
                ->nullable()
                ->description('Descripción detallada'),
            'amount' => $schema->number()
                ->description('Monto a cobrar (ej: 300.50 para $300.50)'),
            'currency_code' => $schema->string()
                ->default('USD')
                ->description('Código de moneda (default: USD)'),
            'reference' => $schema->string()
                ->nullable()
                ->description('Referencia o número de documento relacionado'),
            'is_collected' => $schema->boolean()
                ->default(false)
                ->description('Si ya fue cobrada (true/false)'),
        ];
    }
}

