<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSuperAdmin();
        $this->seedOwnerUser();
        $this->seedCompanyUsers();
    }

    private function seedSuperAdmin(): void
    {
        $role = Role::withoutGlobalScopes()
            ->whereNull('company_id')
            ->where('name', 'super-admin')
            ->first();

        if (! $role) {
            return;
        }

        $user = User::withoutGlobalScopes()->firstOrNew([
            'email' => 'admin@gmail.com',
        ]);
        
        if (!$user->exists) {
            $user->id = (string) Str::uuid();
        }
        
        $user->company_id = null;
        $user->role_id = $role->id;
        $user->first_name = 'Super';
        $user->last_name = 'Administrador';
        $user->password_hash = Hash::make('12345678');
        $user->document_number = null;
        $user->phone_number = null;
        $user->status = 'A';
        $user->save();
    }

    private function seedOwnerUser(): void
    {
        $company = Company::query()->first();
        
        if (! $company) {
            return;
        }

        $role = Role::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('name', 'owner')
            ->first();

        if (! $role) {
            return;
        }

        $user = User::withoutGlobalScopes()->firstOrNew([
            'email' => 'jchox@gmail.com',
        ]);
        
        if (!$user->exists) {
            $user->id = (string) Str::uuid();
        }
        
        $user->company_id = $company->id;
        $user->role_id = $role->id;
        $user->first_name = 'Juan Carlos';
        $user->last_name = 'Hox';
        $user->password_hash = Hash::make('12345678');
        $user->document_number = null;
        $user->phone_number = null;
        $user->status = 'A';
        $user->save();
    }

    private function seedCompanyUsers(): void
    {
        $defaultUsers = [
            'admin' => ['first_name' => 'Administrador', 'last_name' => 'AVI', 'email_prefix' => 'admin'],
            'vendedor' => ['first_name' => 'María', 'last_name' => 'Ventas', 'email_prefix' => 'ventas'],
            'tecnico' => ['first_name' => 'Carlos', 'last_name' => 'Técnico', 'email_prefix' => 'tecnico'],
            'asistente' => ['first_name' => 'Ana', 'last_name' => 'Asistente', 'email_prefix' => 'asistente'],
            'contador' => ['first_name' => 'Javier', 'last_name' => 'Contador', 'email_prefix' => 'contador'],
        ];

        Company::query()->select(['id', 'name'])->chunk(100, function ($companies) use ($defaultUsers): void {
            foreach ($companies as $company) {
                $roles = Role::withoutGlobalScopes()
                    ->where('company_id', $company->id)
                    ->get()
                    ->keyBy('name');

                foreach ($defaultUsers as $roleName => $meta) {
                    $role = $roles->get($roleName);
                    if (! $role) {
                        continue;
                    }

                    $email = sprintf(
                        '%s.%s@%s.avi.dev',
                        $meta['email_prefix'],
                        Str::slug($company->name),
                        date('Y')
                    );

                    $user = User::withoutGlobalScopes()->firstOrNew([
                        'company_id' => $company->id,
                        'email' => $email,
                    ]);
                    
                    if (!$user->exists) {
                        $user->id = (string) Str::uuid();
                    }
                    
                    $user->role_id = $role->id;
                    $user->first_name = Str::upper($meta['first_name']);
                    $user->last_name = Str::upper($meta['last_name']);
                    $user->password_hash = Hash::make('Usuario123!');
                    $user->document_number = null;
                    $user->phone_number = null;
                    $user->status = 'A';
                    $user->save();
                }
            }
        });
    }
}

