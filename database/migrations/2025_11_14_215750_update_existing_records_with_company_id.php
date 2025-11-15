<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Actualiza todos los registros existentes que no tienen company_id
     * y les asigna el company_id de la primera compañía disponible.
     *
     * @return void
     */
    public function up(): void
    {
        // Obtener la primera compañía disponible
        $company = DB::table('companies')->first();
        
        if (!$company) {
            return; // No hay compañías, no se puede actualizar nada
        }

        $companyId = $company->id;

        // Actualizar document_sequences sin company_id
        if (Schema::hasTable('document_sequences') && Schema::hasColumn('document_sequences', 'company_id')) {
            DB::table('document_sequences')
                ->whereNull('company_id')
                ->update(['company_id' => $companyId]);
        }

        // Actualizar branches sin company_id (si es que existen)
        if (Schema::hasTable('branches') && Schema::hasColumn('branches', 'company_id')) {
            DB::table('branches')
                ->whereNull('company_id')
                ->update(['company_id' => $companyId]);
        }

        // Lista de tablas que deben tener company_id
        $tables = [
            'products',
            'customers',
            'services',
            'workshop_orders',
            'workshop_order_advances',
            'workshop_order_notes',
            'workshop_order_items',
            'workshop_order_services',
            'invoices',
            'invoice_items',
            'invoice_payments',
            'product_transfers',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'company_id')) {
                DB::table($table)
                    ->whereNull('company_id')
                    ->update(['company_id' => $companyId]);
            }
        }
    }

    /**
     * Revertir los cambios.
     *
     * @return void
     */
    public function down(): void
    {
        // No se puede revertir esta operación de forma segura
        // ya que perderíamos la relación con las compañías
    }
};
