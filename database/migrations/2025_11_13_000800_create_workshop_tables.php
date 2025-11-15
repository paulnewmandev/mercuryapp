<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta las migraciones.
     */
    public function up(): void
    {
        Schema::create('workshop_categories', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->char('status', 1)->default('A')->comment('A=Activo,I=Inactivo,T=Papelera');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->unique(['company_id', 'name'], 'workshop_categories_company_name_unique');
            $table->index(['company_id', 'status'], 'workshop_categories_company_status_index');
        });

        Schema::create('workshop_states', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->char('status', 1)->default('A')->comment('A=Activo,I=Inactivo,T=Papelera');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->unique(['company_id', 'name'], 'workshop_states_company_name_unique');
            $table->index(['company_id', 'status'], 'workshop_states_company_status_index');
        });
    }

    /**
     * Revierte las migraciones.
     */
    public function down(): void
    {
        Schema::dropIfExists('workshop_states');
        Schema::dropIfExists('workshop_categories');
    }
};


