<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductTransfer;
use App\Models\ProductTransferItem;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductTransferSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::query()->get();

        foreach ($companies as $company) {
            $warehouses = Warehouse::query()
                ->where('company_id', $company->id)
                ->get();

            if ($warehouses->count() < 2) {
                $this->command?->warn("Se necesitan al menos 2 bodegas para crear transferencias. Se omite para la compañía {$company->name}.");
                continue;
            }

            $products = Product::query()
                ->where('company_id', $company->id)
                ->where('status', 'A')
                ->limit(10)
                ->get();

            if ($products->isEmpty()) {
                $this->command?->warn("No hay productos disponibles para crear transferencias. Se omite para la compañía {$company->name}.");
                continue;
            }

            $branch = Branch::query()
                ->where('company_id', $company->id)
                ->first();

            for ($i = 0; $i < 5; $i++) {
                $originWarehouse = $warehouses->random();
                $destinationWarehouse = $warehouses->where('id', '!=', $originWarehouse->id)->random();
                
                $transfer = ProductTransfer::query()->create([
                    'id' => (string) Str::uuid(),
                    'company_id' => $company->id,
                    'origin_warehouse_id' => $originWarehouse->id,
                    'destination_warehouse_id' => $destinationWarehouse->id,
                    'movement_date' => now()->subDays(rand(1, 30)),
                    'reference' => 'TRF-' . strtoupper(Str::random(8)),
                    'notes' => 'Transferencia de inventario generada por seeder.',
                    'status' => 'A',
                ]);

                // Agregar 1-3 productos a la transferencia
                $selectedProducts = $products->random(min(3, $products->count()));
                
                foreach ($selectedProducts as $product) {
                    ProductTransferItem::query()->create([
                        'id' => (string) Str::uuid(),
                        'product_transfer_id' => $transfer->id,
                        'product_id' => $product->id,
                        'quantity' => rand(1, 10),
                        'notes' => null,
                    ]);
                }
            }
        }

        $this->command?->info('Transferencias de inventario generadas correctamente.');
    }
}

