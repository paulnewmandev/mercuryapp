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
        Schema::table('workshop_order_notes', function (Blueprint $table): void {
            // Agregar columna company_id como nullable primero
            $table->uuid('company_id')->nullable()->after('id');
        });

        // Poblar company_id con los valores de workshop_orders
        DB::statement('
            UPDATE workshop_order_notes
            INNER JOIN workshop_orders ON workshop_order_notes.order_id = workshop_orders.id
            SET workshop_order_notes.company_id = workshop_orders.company_id
        ');

        // Hacer la columna NOT NULL y agregar foreign key e índice
        Schema::table('workshop_order_notes', function (Blueprint $table): void {
            $table->uuid('company_id')->nullable(false)->change();
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->index(['company_id', 'order_id'], 'workshop_order_notes_company_order_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workshop_order_notes', function (Blueprint $table): void {
            $table->dropForeign(['company_id']);
            $table->dropIndex('workshop_order_notes_company_order_index');
            $table->dropColumn('company_id');
        });
    }
};
