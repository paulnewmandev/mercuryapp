<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\WorkshopOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatToolController extends Controller
{
    public function __construct()
    {
        // El middleware 'auth' se aplica en las rutas
    }

    /**
     * Busca un cliente por número de documento.
     */
    public function searchCustomerByDocument(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'document_number' => ['required', 'string'],
        ]);

        $user = Auth::user();
        $companyId = $user->company_id;

        if (!$companyId) {
            $companyId = \App\Models\Company::query()->first()?->id;
        }

        $customer = Customer::where('company_id', $companyId)
            ->where('document_number', $validated['document_number'])
            ->with(['category'])
            ->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'exists' => false,
                'message' => 'Cliente no encontrado',
            ]);
        }

        return response()->json([
            'success' => true,
            'exists' => true,
            'customer' => [
                'id' => $customer->id,
                'type' => $customer->customer_type,
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'business_name' => $customer->business_name,
                'document_type' => $customer->document_type,
                'document_number' => $customer->document_number,
                'email' => $customer->email,
                'phone_number' => $customer->phone_number,
                'address' => $customer->address,
                'status' => $customer->status,
                'category' => $customer->category?->name,
            ],
        ]);
    }

    /**
     * Obtiene una orden de trabajo por número.
     */
    public function getWorkshopOrderByNumber(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_number' => ['required', 'string'],
        ]);

        $user = Auth::user();
        $companyId = $user->company_id;

        if (!$companyId) {
            $companyId = \App\Models\Company::query()->first()?->id;
        }

        $order = WorkshopOrder::where('company_id', $companyId)
            ->where('order_number', $validated['order_number'])
            ->with([
                'customer',
                'responsible',
                'equipment',
                'equipment.brand',
                'equipment.model',
                'state',
                'items.product',
                'services.service',
                'notes',
            ])
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'exists' => false,
                'message' => 'Orden de trabajo no encontrada',
            ]);
        }

        return response()->json([
            'success' => true,
            'exists' => true,
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->state?->name ?? 'Sin estado',
                'status_description' => $order->state?->description,
                'customer' => [
                    'name' => ($order->customer?->first_name ?? '') . ' ' . ($order->customer?->last_name ?? ''),
                    'document' => $order->customer?->document_number,
                ],
                'equipment' => [
                    'name' => $order->equipment?->name,
                    'brand' => $order->equipment?->brand?->name,
                    'model' => $order->equipment?->model?->name,
                ],
                'total' => $order->total_cost ?? 0,
                'advance_amount' => $order->advance_amount ?? 0,
                'remaining' => ($order->total_cost ?? 0) - ($order->advance_amount ?? 0),
                'promised_at' => $order->promised_at?->format('Y-m-d'),
                'created_at' => $order->created_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Busca productos por nombres.
     */
    public function searchProductsByNames(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_names' => ['required', 'array'],
            'product_names.*' => ['required', 'string'],
        ]);

        $user = Auth::user();
        $companyId = $user->company_id;

        if (!$companyId) {
            $companyId = \App\Models\Company::query()->first()?->id;
        }

        $results = [];
        
        foreach ($validated['product_names'] as $productName) {
            $products = Product::where('company_id', $companyId)
                ->where('status', 'A')
                ->where(function ($query) use ($productName) {
                    $query->where('name', 'like', "%{$productName}%")
                        ->orWhere('sku', 'like', "%{$productName}%")
                        ->orWhere('barcode', 'like', "%{$productName}%");
                })
                ->with(['line', 'category', 'warehouse'])
                ->get();

            $results[$productName] = $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'barcode' => $product->barcode,
                    'stock' => $product->stock ?? 0,
                    'price' => $this->getProductPrice($product),
                    'warehouse' => $product->warehouse?->name,
                ];
            })->toArray();
        }

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }

    /**
     * Obtiene el precio de un producto.
     */
    private function getProductPrice(Product $product): ?float
    {
        if ($product->price_list_pos_id) {
            $priceList = \App\Models\PriceList::find($product->price_list_pos_id);
            if ($priceList) {
                $itemPrice = \App\Models\ItemPrice::where('price_list_id', $priceList->id)
                    ->where('product_id', $product->id)
                    ->first();
                
                return $itemPrice?->price ?? null;
            }
        }

        return null;
    }
}
