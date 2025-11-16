<?php

namespace App\Mcp\Tools\Chatbot;

use App\Models\Expense;
use App\Models\ExpenseType;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ExpenseCreateTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Crea un nuevo egreso en el sistema. Requiere: tipo de egreso, fecha, concepto, monto.
        Sinónimos: agregar egreso, registrar egreso, nuevo egreso, crear egreso, gasto, registrar gasto.
        Ejemplos: "agrega un egreso de 200 USD del 15 de enero por compra de materiales", "registra un gasto de 150 por servicios públicos".
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

        // Obtener o crear tipo de egreso
        $expenseTypeId = $arguments['expense_type_id'] ?? null;
        if (!$expenseTypeId) {
            // Buscar tipo por nombre o usar el primero disponible
            $typeName = $arguments['expense_type_name'] ?? 'General';
            $expenseType = ExpenseType::where('company_id', $companyId)
                ->where('name', 'like', "%{$typeName}%")
                ->first();
            
            if (!$expenseType) {
                $expenseType = ExpenseType::where('company_id', $companyId)->first();
            }
            
            if (!$expenseType) {
                return Response::text('<div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"><p class="text-red-800 dark:text-red-200">No se encontró un tipo de egreso. Por favor, crea uno primero.</p></div>');
            }
            
            $expenseTypeId = $expenseType->id;
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
            $expense = Expense::create([
                'company_id' => $companyId,
                'expense_type_id' => $expenseTypeId,
                'movement_date' => $movementDate,
                'concept' => $arguments['concept'],
                'description' => $arguments['description'] ?? null,
                'amount_cents' => $amountCents,
                'currency_code' => $arguments['currency_code'] ?? 'USD',
                'reference' => $arguments['reference'] ?? null,
                'status' => $arguments['status'] ?? 'A',
            ]);

            $expense->load('type');

            $output = '<div class="space-y-4">';
            $output .= '<div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">';
            $output .= '<p class="text-green-800 dark:text-green-200 font-semibold mb-2">✓ Egreso creado correctamente</p>';
            $output .= '</div>';
            
            $output .= '<div class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-2">';
            $output .= '<h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Detalles del Egreso</h3>';
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[140px]">Tipo:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($expense->type?->name ?? 'N/A') . '</span></div>';
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[140px]">Concepto:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($expense->concept) . '</span></div>';
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[140px]">Monto:</span><span class="text-red-600 dark:text-red-400 font-semibold">' . $expense->amount_formatted . '</span></div>';
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[140px]">Fecha:</span><span class="text-gray-900 dark:text-white">' . $expense->movement_date->format('d/m/Y') . '</span></div>';
            
            if ($expense->description) {
                $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[140px]">Descripción:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($expense->description) . '</span></div>';
            }
            
            if ($expense->reference) {
                $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[140px]">Referencia:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($expense->reference) . '</span></div>';
            }
            
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[140px]">Estado:</span><span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ' . ($expense->status === 'A' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400') . '">' . ($expense->status === 'A' ? 'Activo' : 'Inactivo') . '</span></div>';
            $output .= '</div></div>';

            return Response::text($output);
        } catch (\Exception $e) {
            Log::error("Error al crear egreso: " . $e->getMessage());
            return Response::text('<div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"><p class="text-red-800 dark:text-red-200">Error al crear el egreso: ' . htmlspecialchars($e->getMessage()) . '</p></div>');
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'expense_type_id' => $schema->string()
                ->nullable()
                ->description('ID del tipo de egreso'),
            'expense_type_name' => $schema->string()
                ->nullable()
                ->description('Nombre del tipo de egreso (si no se proporciona ID, busca por nombre)'),
            'movement_date' => $schema->string()
                ->description('Fecha del egreso (formato: YYYY-MM-DD)'),
            'concept' => $schema->string()
                ->description('Concepto del egreso (ej: "Compra de materiales", "Servicios públicos")'),
            'description' => $schema->string()
                ->nullable()
                ->description('Descripción detallada del egreso'),
            'amount' => $schema->number()
                ->description('Monto del egreso (ej: 200.50 para $200.50)'),
            'currency_code' => $schema->string()
                ->default('USD')
                ->description('Código de moneda (default: USD)'),
            'reference' => $schema->string()
                ->nullable()
                ->description('Referencia o número de documento relacionado'),
            'status' => $schema->string()
                ->enum(['A', 'I'])
                ->default('A')
                ->description('Estado: A (Activo) o I (Inactivo)'),
        ];
    }
}

