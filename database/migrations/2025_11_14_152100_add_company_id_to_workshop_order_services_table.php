<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Verificar si la columna company_id ya existe
        if (!Schema::hasColumn('workshop_order_services', 'company_id')) {
            Schema::table('workshop_order_services', function (Blueprint $table): void {
                // Agregar columna company_id como nullable primero
                $table->uuid('company_id')->nullable()->after('id');
            });

            // Poblar company_id con los valores de workshop_orders
            DB::statement('
                UPDATE workshop_order_services
                INNER JOIN workshop_orders ON workshop_order_services.order_id = workshop_orders.id
                SET workshop_order_services.company_id = workshop_orders.company_id
            ');

            // Hacer la columna NOT NULL y agregar foreign key e índice
            Schema::table('workshop_order_services', function (Blueprint $table): void {
                $table->uuid('company_id')->nullable(false)->change();
            });
        }

        // Agregar foreign key e índice si no existen (usar try-catch para evitar errores si ya existen)
        try {
            Schema::table('workshop_order_services', function (Blueprint $table): void {
                $table->index(['company_id', 'order_id'], 'workshop_order_services_company_order_index');
            });
        } catch (\Exception $e) {
            // El índice ya existe, continuar
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workshop_order_services', function (Blueprint $table): void {
            $table->dropForeign(['company_id']);
            $table->dropIndex('workshop_order_services_company_order_index');
            $table->dropColumn('company_id');
        });
    }
};

