<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeder para generar sucursales de demostración asociadas a la compañía principal.
 */
class BranchSeeder extends Seeder
{
    /**
     * Ejecuta la siembra de datos.
     */
    public function run(): void
    {
        $company = Company::query()->first();

        if (! $company) {
            $this->command?->warn('No se encontró una compañía para asociar las sucursales.');
            return;
        }

        $branches = collect([
            [
                'code' => 'MAT-QIT',
                'name' => 'Matriz Quito',
                'address' => 'Av. Amazonas y República, Quito',
                'email' => 'matriz@avi.ec',
                'phone_number' => '+593 02 600 1122',
                'latitude' => -0.180653,
                'longitude' => -78.467834,
            ],
            [
                'code' => 'ESP-RIO',
                'name' => 'Especialista Río Centro Mall Quito',
                'address' => 'Ríocentro Shopping, Av. Naciones Unidas, Quito',
                'email' => 'especialista@avi.ec',
                'phone_number' => '+593 02 700 3344',
                'latitude' => -0.176550,
                'longitude' => -78.478300,
            ],
        ]);

        $codesToKeep = $branches->pluck('code');

        Branch::query()
            ->where('company_id', $company->id)
            ->whereNotIn('code', $codesToKeep)
            ->delete();

        $branches->each(function (array $branch) use ($company): void {
            $branchModel = Branch::query()->firstOrNew([
                'company_id' => $company->id,
                'code' => $branch['code'],
            ]);
            
            if (!$branchModel->exists) {
                $branchModel->id = (string) Str::uuid();
            }
            
            $branchModel->fill(array_merge($branch, [
                'company_id' => $company->id,
                'status' => 'A',
            ]));
            $branchModel->save();
        });

        $this->command?->info('Sucursales principales actualizadas correctamente.');
    }
}
