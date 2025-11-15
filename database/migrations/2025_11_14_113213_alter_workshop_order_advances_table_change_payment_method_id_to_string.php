<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta las migraciones.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('workshop_order_advances', function (Blueprint $table): void {
            // Eliminar la foreign key primero
            $table->dropForeign(['payment_method_id']);
            
            // Cambiar el tipo de columna de UUID a string
            $table->string('payment_method_id', 50)->nullable()->change();
        });
    }

    /**
     * Revierte las migraciones.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('workshop_order_advances', function (Blueprint $table): void {
            // Cambiar el tipo de columna de string a UUID
            $table->uuid('payment_method_id')->nullable()->change();
            
            // Restaurar la foreign key
            $table->foreign('payment_method_id')->references('id')->on('payment_methods')->nullOnDelete();
        });
    }
};
