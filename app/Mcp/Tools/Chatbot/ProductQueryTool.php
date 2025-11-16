<?php

namespace App\Mcp\Tools\Chatbot;

use App\Models\Product;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ProductQueryTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Obtiene TODOS los detalles completos de un producto por su SKU, código de barras o nombre.
        Incluye información completa: nombre, descripción, SKU, código de barras, stock, precios, categorías, almacén, líneas de producto, etc.
        Útil para consultas como: "dame los detalles del producto 001-001-001" o "muéstrame todos los detalles del producto X"
    MARKDOWN;

    public function handle(Request $request): Response
    {
        if (!Auth::check()) {
            return Response::error('Usuario debe estar autenticado');
        }

        $arguments = $request->all();
        $productNames = $arguments['product_names'] ?? [];
        $sku = $arguments['sku'] ?? null;
        $barcode = $arguments['barcode'] ?? null;
        $categoryName = $arguments['category_name'] ?? null;

        if (empty($productNames) && !$sku && !$barcode && !$categoryName) {
            return Response::error('Debe proporcionar product_names, sku, barcode o category_name');
        }

        $user = Auth::user();
        $companyId = $user->company_id;

        if (!$companyId) {
            $companyId = \App\Models\Company::query()->first()?->id;
        }

        if (!$companyId) {
            return Response::error('No se encontró una compañía asociada');
        }

        $output = '';

        // Búsqueda por SKU (detalles completos)
        if ($sku) {
            $products = Product::where('company_id', $companyId)
                ->where('sku', $sku)
                ->with(['line', 'category', 'subcategory', 'warehouse', 'priceListPos', 'priceListB2c', 'priceListB2b'])
                ->get();

                if ($products->isEmpty()) {
                return Response::text('<div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"><p class="text-red-800 dark:text-red-200">No se encontró ningún producto con SKU: ' . htmlspecialchars($sku) . '</p></div>');
            }

            foreach ($products as $product) {
                $output .= $this->generateProductDetails($product);
            }

            return Response::text($output);
        }

        // Búsqueda por código de barras (detalles completos)
        if ($barcode) {
            $products = Product::where('company_id', $companyId)
                ->where('barcode', $barcode)
                ->with(['line', 'category', 'subcategory', 'warehouse', 'priceListPos', 'priceListB2c', 'priceListB2b'])
                ->get();

            if ($products->isEmpty()) {
                return Response::text('<div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"><p class="text-red-800 dark:text-red-200">No se encontró ningún producto con código de barras: ' . htmlspecialchars($barcode) . '</p></div>');
            }

                foreach ($products as $product) {
                $output .= $this->generateProductDetails($product);
            }

            return Response::text($output);
        }

        // Búsqueda por nombre (detalles completos si es uno solo, lista si son varios)
        if (!empty($productNames)) {
            $allProducts = collect();
            
            foreach ($productNames as $productName) {
                $products = Product::where('company_id', $companyId)
                    ->where('status', 'A')
                    ->where(function($q) use ($productName) {
                        $q->where('name', 'like', "%{$productName}%")
                          ->orWhere('sku', 'like', "%{$productName}%")
                          ->orWhere('barcode', 'like', "%{$productName}%");
                    })
                    ->with(['line', 'category', 'subcategory', 'warehouse', 'priceListPos', 'priceListB2c', 'priceListB2b'])
                    ->limit(5)
                ->get();

                $allProducts = $allProducts->merge($products);
            }

            if ($allProducts->isEmpty()) {
                return Response::text('<div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"><p class="text-red-800 dark:text-red-200">No se encontraron productos con los nombres proporcionados.</p></div>');
            }

            // Si solo hay un producto, mostrar detalles completos
            if ($allProducts->count() === 1) {
                $output .= $this->generateProductDetails($allProducts->first());
            } else {
                // Si hay varios, mostrar lista
                $output .= '<div class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 mb-6">';
                $output .= '<h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Productos Encontrados (' . $allProducts->count() . ')</h2>';
                $output .= '<div class="overflow-x-auto">';
                $output .= '<table class="w-full min-w-[800px] divide-y divide-gray-200 dark:divide-gray-700">';
                $output .= '<thead class="bg-gray-50 dark:bg-gray-900">';
                $output .= '<tr>';
                $output .= '<th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nombre</th>';
                $output .= '<th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">SKU</th>';
                $output .= '<th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Categoría</th>';
                $output .= '<th class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Stock</th>';
                $output .= '<th class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Precio</th>';
                $output .= '<th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>';
                $output .= '</tr>';
                $output .= '</thead>';
                $output .= '<tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-gray-700">';
                
                foreach ($allProducts as $product) {
                    $price = $this->getProductPrice($product);
                    $output .= '<tr class="hover:bg-gray-50 dark:hover:bg-gray-700">';
                    $output .= '<td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">' . htmlspecialchars($product->name) . '</td>';
                    $output .= '<td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">' . htmlspecialchars($product->sku ?? '-') . '</td>';
                    $output .= '<td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">' . htmlspecialchars($product->category?->name ?? '-') . '</td>';
                    $output .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">' . number_format($product->stock ?? 0, 0) . '</td>';
                    $output .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">' . ($price ? 'USD ' . number_format($price, 2) : '-') . '</td>';
                    $output .= '<td class="px-6 py-4 whitespace-nowrap text-sm">';
                    $output .= '<span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold ' . ($product->status === 'A' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200') . '">';
                    $output .= $product->status === 'A' ? 'Activo' : 'Inactivo';
                    $output .= '</span>';
                    $output .= '</td>';
                    $output .= '</tr>';
                }
                
                $output .= '</tbody>';
                $output .= '</table>';
                $output .= '</div>';
                $output .= '</div>';
            }

            return Response::text($output);
        }

        // Búsqueda por categoría
        if ($categoryName) {
            $products = Product::where('company_id', $companyId)
                ->where('status', 'A')
                ->whereHas('category', function($q) use ($categoryName) {
                $q->where('name', 'like', "%{$categoryName}%");
            })
            ->with(['line', 'category', 'warehouse', 'priceListPos'])
                ->limit(20)
            ->get();

            if ($products->isEmpty()) {
                return Response::text('<div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"><p class="text-red-800 dark:text-red-200">No se encontraron productos en la categoría: ' . htmlspecialchars($categoryName) . '</p></div>');
            }

            $output .= '<div class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 mb-6">';
            $output .= '<h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Categoría: ' . htmlspecialchars($categoryName) . ' (' . $products->count() . ' productos)</h2>';
            $output .= '<div class="overflow-x-auto">';
            $output .= '<table class="w-full min-w-[800px] divide-y divide-gray-200 dark:divide-gray-700">';
            $output .= '<thead class="bg-gray-50 dark:bg-gray-900">';
            $output .= '<tr>';
            $output .= '<th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nombre</th>';
            $output .= '<th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">SKU</th>';
            $output .= '<th class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Stock</th>';
            $output .= '<th class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Precio</th>';
            $output .= '</tr>';
            $output .= '</thead>';
            $output .= '<tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-gray-700">';
            
                foreach ($products as $product) {
                $price = $this->getProductPrice($product);
                $output .= '<tr class="hover:bg-gray-50 dark:hover:bg-gray-700">';
                $output .= '<td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">' . htmlspecialchars($product->name) . '</td>';
                $output .= '<td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">' . htmlspecialchars($product->sku ?? '-') . '</td>';
                $output .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">' . number_format($product->stock ?? 0, 0) . '</td>';
                $output .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">' . ($price ? 'USD ' . number_format($price, 2) : '-') . '</td>';
                $output .= '</tr>';
            }
            
            $output .= '</tbody>';
            $output .= '</table>';
            $output .= '</div>';
            $output .= '</div>';

            return Response::text($output);
        }

        return Response::text($output);
    }

    private function generateProductDetails(Product $product): string
    {
        $output = '<div class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 mb-6">';
        $output .= '<h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">' . htmlspecialchars($product->name) . '</h2>';
        
        // Información básica
        $output .= '<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">';
        
        // Identificadores
        $output .= '<div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">';
        $output .= '<div class="font-semibold text-blue-900 dark:text-blue-300 mb-2">Identificadores</div>';
        $output .= '<div class="space-y-1 text-sm text-blue-700 dark:text-blue-400">';
        $output .= '<div><strong>SKU:</strong> ' . htmlspecialchars($product->sku ?? 'N/A') . '</div>';
        if ($product->barcode) {
            $output .= '<div><strong>Código de barras:</strong> ' . htmlspecialchars($product->barcode) . '</div>';
        }
        $output .= '</div>';
        $output .= '</div>';
        
        // Estado y disponibilidad
        $output .= '<div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">';
        $output .= '<div class="font-semibold text-green-900 dark:text-green-300 mb-2">Estado y Disponibilidad</div>';
        $output .= '<div class="space-y-1 text-sm text-green-700 dark:text-green-400">';
        $output .= '<div><strong>Estado:</strong> <span class="' . ($product->status === 'A' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400') . ' font-semibold">' . ($product->status === 'A' ? 'Activo' : 'Inactivo') . '</span></div>';
        $output .= '<div><strong>Stock:</strong> ' . number_format($product->stock ?? 0, 0) . ' unidades</div>';
        $output .= '</div>';
        $output .= '</div>';
        
        // Categorías
        if ($product->category || $product->subcategory) {
            $output .= '<div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 border border-purple-200 dark:border-purple-800">';
            $output .= '<div class="font-semibold text-purple-900 dark:text-purple-300 mb-2">Categorías</div>';
            $output .= '<div class="space-y-1 text-sm text-purple-700 dark:text-purple-400">';
            if ($product->category) {
                $output .= '<div><strong>Categoría:</strong> ' . htmlspecialchars($product->category->name) . '</div>';
            }
            if ($product->subcategory) {
                $output .= '<div><strong>Subcategoría:</strong> ' . htmlspecialchars($product->subcategory->name) . '</div>';
            }
            $output .= '</div>';
            $output .= '</div>';
        }
        
        // Línea de producto
        if ($product->line) {
            $output .= '<div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 border border-yellow-200 dark:border-yellow-800">';
            $output .= '<div class="font-semibold text-yellow-900 dark:text-yellow-300 mb-2">Línea de Producto</div>';
            $output .= '<div class="text-sm text-yellow-700 dark:text-yellow-400">' . htmlspecialchars($product->line->name ?? 'N/A') . '</div>';
            $output .= '</div>';
        }
        
        // Almacén
        if ($product->warehouse) {
            $output .= '<div class="bg-gray-50 dark:bg-gray-900/20 rounded-lg p-4 border border-gray-200 dark:border-gray-800">';
            $output .= '<div class="font-semibold text-gray-900 dark:text-gray-300 mb-2">Almacén</div>';
            $output .= '<div class="text-sm text-gray-700 dark:text-gray-400">' . htmlspecialchars($product->warehouse->name ?? 'N/A') . '</div>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        // Descripción
        if ($product->description) {
            $output .= '<div class="mb-6">';
            $output .= '<h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Descripción</h3>';
            $output .= '<div class="bg-gray-50 dark:bg-gray-900/20 rounded-lg p-4 border border-gray-200 dark:border-gray-800">';
            $output .= '<div class="text-gray-900 dark:text-white whitespace-pre-wrap">' . nl2br(htmlspecialchars($product->description)) . '</div>';
            $output .= '</div>';
            $output .= '</div>';
        }
        
        // Precios
        $output .= '<div class="mb-6">';
        $output .= '<h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Precios</h3>';
        $output .= '<div class="grid grid-cols-1 md:grid-cols-3 gap-4">';
        
        $posPrice = $this->getProductPrice($product, 'pos');
        $b2cPrice = $this->getProductPrice($product, 'b2c');
        $b2bPrice = $this->getProductPrice($product, 'b2b');
        
        if ($posPrice) {
            $output .= '<div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">';
            $output .= '<div class="font-semibold text-blue-900 dark:text-blue-300 mb-2">Precio POS</div>';
            $output .= '<div class="text-2xl font-bold text-blue-700 dark:text-blue-400">USD ' . number_format($posPrice, 2) . '</div>';
            $output .= '</div>';
        }
        
        if ($b2cPrice) {
            $output .= '<div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">';
            $output .= '<div class="font-semibold text-green-900 dark:text-green-300 mb-2">Precio B2C</div>';
            $output .= '<div class="text-2xl font-bold text-green-700 dark:text-green-400">USD ' . number_format($b2cPrice, 2) . '</div>';
            $output .= '</div>';
        }
        
        if ($b2bPrice) {
            $output .= '<div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 border border-purple-200 dark:border-purple-800">';
            $output .= '<div class="font-semibold text-purple-900 dark:text-purple-300 mb-2">Precio B2B</div>';
            $output .= '<div class="text-2xl font-bold text-purple-700 dark:text-purple-400">USD ' . number_format($b2bPrice, 2) . '</div>';
            $output .= '</div>';
        }
        
        if (!$posPrice && !$b2cPrice && !$b2bPrice) {
            $output .= '<div class="col-span-3 bg-gray-50 dark:bg-gray-900/20 rounded-lg p-4 border border-gray-200 dark:border-gray-800">';
            $output .= '<div class="text-gray-600 dark:text-gray-400">No hay precios configurados para este producto</div>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        $output .= '</div>';
        
        // Configuración de visualización
        $output .= '<div class="mb-6">';
        $output .= '<h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Configuración de Visualización</h3>';
        $output .= '<div class="bg-gray-50 dark:bg-gray-900/20 rounded-lg p-4 border border-gray-200 dark:border-gray-800">';
        $output .= '<div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">';
        $output .= '<div><strong>Mostrar en POS:</strong> ' . ($product->show_in_pos ? 'Sí' : 'No') . '</div>';
        $output .= '<div><strong>Mostrar en B2C:</strong> ' . ($product->show_in_b2c ? 'Sí' : 'No') . '</div>';
        $output .= '<div><strong>Mostrar en B2B:</strong> ' . ($product->show_in_b2b ? 'Sí' : 'No') . '</div>';
        $output .= '</div>';
        $output .= '</div>';
        $output .= '</div>';
        
        $output .= '</div>';
        
        return $output;
    }

    private function getProductPrice(Product $product, ?string $type = null): ?float
    {
        // Estructura de item_prices: item_id, item_type, price_list_id, value
        $lookup = function (?string $priceListId) use ($product): ?float {
            if (!$priceListId) {
                return null;
            }
            $itemPrice = \App\Models\ItemPrice::query()
                ->where('price_list_id', $priceListId)
                ->where('item_id', $product->id)
                ->where('item_type', 'product')
                ->where('status', 'A')
                ->first();
            return $itemPrice?->value ?? null;
        };

        if ($type === 'pos' || !$type) {
            $val = $lookup($product->price_list_pos_id);
            if ($val !== null) return $val;
        }
        if ($type === 'b2c' || !$type) {
            $val = $lookup($product->price_list_b2c_id);
            if ($val !== null) return $val;
        }
        if ($type === 'b2b' || !$type) {
            $val = $lookup($product->price_list_b2b_id);
            if ($val !== null) return $val;
        }
        return null;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'product_names' => $schema->array()
                ->items($schema->string())
                ->nullable()
                ->description('Lista de nombres de productos a buscar (si solo hay uno, se muestran todos los detalles)'),
            'sku' => $schema->string()
                ->nullable()
                ->description('SKU del producto para obtener TODOS los detalles completos'),
            'barcode' => $schema->string()
                ->nullable()
                ->description('Código de barras del producto para obtener TODOS los detalles completos'),
            'category_name' => $schema->string()
                ->nullable()
                ->description('Nombre de la categoría para buscar todos sus productos'),
        ];
    }
}
