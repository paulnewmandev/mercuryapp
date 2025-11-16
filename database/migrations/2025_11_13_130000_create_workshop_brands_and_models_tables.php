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
        Schema::create('workshop_brands', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->char('status', 1)->default('A')->comment('A=Activo,I=Inactivo,T=Papelera');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->unique(['company_id', 'name'], 'workshop_brands_company_name_unique');
            $table->index(['company_id', 'status'], 'workshop_brands_company_status_index');
        });

        Schema::create('workshop_models', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('brand_id');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->char('status', 1)->default('A')->comment('A=Activo,I=Inactivo,T=Papelera');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('brand_id')->references('id')->on('workshop_brands')->cascadeOnDelete();
            $table->unique(['company_id', 'brand_id', 'name'], 'workshop_models_company_brand_name_unique');
            $table->index(['company_id', 'brand_id'], 'workshop_models_company_brand_index');
            $table->index(['company_id', 'status'], 'workshop_models_company_status_index');
        });
    }

    /**
     * Revierte las migraciones.
     */
    public function down(): void
    {
        Schema::dropIfExists('workshop_models');
        Schema::dropIfExists('workshop_brands');
    }
};
