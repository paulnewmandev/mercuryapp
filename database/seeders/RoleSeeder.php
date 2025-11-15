<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSuperAdminRole();
        $this->seedCompanyRoles();
    }

    private function seedSuperAdminRole(): void
    {
        $role = Role::withoutGlobalScopes()->firstOrNew([
            'company_id' => null,
            'name' => 'super-admin',
        ]);
        
        if (!$role->exists) {
            $role->id = (string) Str::uuid();
        }
        
        $role->display_name = 'Super administrador';
        $role->description = 'Acceso total al panel global de AVI.';
        $role->status = 'A';
        $role->save();
    }

    private function seedCompanyRoles(): void
    {
        $roles = [
            'admin' => [
                'display' => 'Administrador',
                'description' => 'Gestiona todo el módulo de la compañía.',
            ],
            'vendedor' => [
                'display' => 'Vendedor',
                'description' => 'Acceso a ventas, cotizaciones y seguimiento comercial.',
            ],
            'tecnico' => [
                'display' => 'Técnico',
                'description' => 'Gestiona órdenes de trabajo y reportes técnicos.',
            ],
            'asistente' => [
                'display' => 'Asistente',
                'description' => 'Apoya tareas administrativas y de atención al cliente.',
            ],
            'contador' => [
                'display' => 'Contador',
                'description' => 'Supervisa ingresos, egresos y reportes contables.',
            ],
        ];

        Company::query()->select(['id'])->chunk(100, function ($companies) use ($roles): void {
            foreach ($companies as $company) {
                foreach ($roles as $slug => $meta) {
                    $role = Role::withoutGlobalScopes()->firstOrNew([
                        'company_id' => $company->id,
                        'name' => $slug,
                    ]);
                    
                    if (!$role->exists) {
                        $role->id = (string) Str::uuid();
                    }
                    
                    $role->display_name = Arr::get($meta, 'display', Str::headline($slug));
                    $role->description = Arr::get($meta, 'description');
                    $role->status = 'A';
                    $role->save();
                }
            }
        });
    }
}

