<?php

namespace App\Mcp\Tools\Chatbot;

use App\Models\Product;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ProductUpdateTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Actualiza datos de un producto existente. Puede actualizar: nombre, descripción, SKU, stock, precio, estado, etc.
        Requiere el ID, SKU o nombre del producto para identificarlo.
        Sinónimos: actualizar producto, modificar producto, cambiar datos del producto, editar producto, actualizar stock, cambiar precio del producto.
        Ejemplos: "actualiza el stock del producto iPhone 13 a 50", "cambia el precio del producto con SKU ABC123".
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

        // Identificar producto
        $productId = $arguments['product_id'] ?? null;
        $sku = $arguments['sku'] ?? null;
        $name = $arguments['name'] ?? null;

        if (!$productId && !$sku && !$name) {
            return Response::error('Debe proporcionar product_id, sku o name para identificar al producto');
        }

        // Buscar producto
        $query = Product::where('company_id', $companyId);

        if ($productId) {
            $query->where('id', $productId);
        } elseif ($sku) {
            $query->where('sku', $sku);
        } elseif ($name) {
            $query->where('name', 'like', "%{$name}%");
        }

        $product = $query->first();

        if (!$product) {
            return Response::text('<div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"><p class="text-red-800 dark:text-red-200">No se encontró el producto especificado.</p></div>');
        }

        // Preparar datos a actualizar
        $updateData = [];

        if (isset($arguments['name'])) {
            $updateData['name'] = $arguments['name'];
        }

        if (isset($arguments['description'])) {
            $updateData['description'] = $arguments['description'];
        }

        if (isset($arguments['sku'])) {
            // Validar que el SKU no esté en uso por otro producto
            $skuExists = Product::where('company_id', $companyId)
                ->where('sku', $arguments['sku'])
                ->where('id', '!=', $product->id)
                ->exists();
            
            if ($skuExists) {
                return Response::text('<div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"><p class="text-red-800 dark:text-red-200">El SKU ' . htmlspecialchars($arguments['sku']) . ' ya está registrado para otro producto.</p></div>');
            }
            
            $updateData['sku'] = $arguments['sku'];
        }

        if (isset($arguments['stock'])) {
            $updateData['stock'] = max(0, (int) $arguments['stock']);
        }

        if (isset($arguments['status'])) {
            if (!in_array($arguments['status'], ['A', 'I'])) {
                return Response::text('<div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"><p class="text-red-800 dark:text-red-200">El estado debe ser A (Activo) o I (Inactivo).</p></div>');
            }
            $updateData['status'] = $arguments['status'];
        }

        if (empty($updateData)) {
            return Response::text('<div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg"><p class="text-yellow-800 dark:text-yellow-200">No se proporcionaron datos para actualizar.</p></div>');
        }

        try {
            $product->update($updateData);
            $product->refresh();

            $output = '<div class="space-y-4">';
            $output .= '<div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">';
            $output .= '<p class="text-green-800 dark:text-green-200 font-semibold mb-2">✓ Producto actualizado correctamente</p>';
            $output .= '</div>';
            
            $output .= '<div class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-2">';
            $output .= '<h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Datos Actualizados</h3>';
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[120px]">Nombre:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($product->name) . '</span></div>';
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[120px]">SKU:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($product->sku ?? 'N/A') . '</span></div>';
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[120px]">Stock:</span><span class="text-gray-900 dark:text-white">' . ($product->stock ?? 0) . '</span></div>';
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[120px]">Estado:</span><span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ' . ($product->status === 'A' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400') . '">' . ($product->status === 'A' ? 'Activo' : 'Inactivo') . '</span></div>';
            
            if ($product->description) {
                $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[120px]">Descripción:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($product->description) . '</span></div>';
            }
            
            $output .= '</div></div>';

            return Response::text($output);
        } catch (\Exception $e) {
            Log::error("Error al actualizar producto: " . $e->getMessage());
            return Response::text('<div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"><p class="text-red-800 dark:text-red-200">Error al actualizar el producto: ' . htmlspecialchars($e->getMessage()) . '</p></div>');
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'product_id' => $schema->string()
                ->nullable()
                ->description('ID del producto a actualizar'),
            'sku' => $schema->string()
                ->nullable()
                ->description('SKU del producto a actualizar (puede ser para identificar o para actualizar)'),
            'name' => $schema->string()
                ->nullable()
                ->description('Nombre del producto a actualizar (puede ser para identificar o para actualizar)'),
            'description' => $schema->string()
                ->nullable()
                ->description('Nueva descripción del producto'),
            'stock' => $schema->integer()
                ->nullable()
                ->description('Nuevo stock del producto'),
            'status' => $schema->string()
                ->enum(['A', 'I'])
                ->nullable()
                ->description('Nuevo estado: A (Activo) o I (Inactivo)'),
        ];
    }
}

