<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\ProductLine;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class ProductLineSeeder extends Seeder
{
    public function run(): void
    {
        $lines = [
            'Accesorios' => 'Accesorios originales, premium y universales para complementar los dispositivos.',
            'Cargador' => 'Soluciones de carga rápida, inalámbrica y multipuerto.',
            'Baterias' => 'Baterías de reemplazo certificadas y kits de instalación.',
            'Celulares' => 'Smartphones de última generación y modelos icónicos.',
            'Laptops' => 'Portátiles de alto rendimiento para uso profesional y creativo.',
            'Tablets' => 'Tabletas para productividad, entretenimiento y educación.',
            'SmartWatch' => 'Relojes inteligentes y wearables con enfoque fitness y productividad.',
            'Usados' => 'Equipos reacondicionados certificados y con garantía.',
            'Repuestos' => 'Componentes internos, módulos y partes para reparación.',
        ];

        $categoriesPath = database_path('seeders/data/product_categories.json');
        if (File::exists($categoriesPath)) {
            $categories = json_decode(File::get($categoriesPath), true) ?? [];

            foreach (array_keys($categories) as $categoryName) {
                if (! \array_key_exists($categoryName, $lines)) {
                    $lines[$categoryName] = sprintf(
                        'Línea generada desde catálogo Excel (%s).',
                        Str::title($categoryName)
                    );
                }
            }
        }

        $companies = Company::query()->get();

        foreach ($companies as $company) {
            ProductLine::query()
                ->where('company_id', $company->id)
                ->whereNotIn('name', array_keys($lines))
                ->delete();

            foreach ($lines as $name => $description) {
                $productLine = ProductLine::query()->firstOrNew([
                        'company_id' => $company->id,
                        'name' => $name,
                ]);
                
                if (!$productLine->exists) {
                    $productLine->id = (string) Str::uuid();
                }
                
                $productLine->description = $description;
                $productLine->status = 'A';
                $productLine->save();
            }
        }
    }
}
