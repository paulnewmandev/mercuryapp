<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workshop_equipments', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('brand_id');
            $table->uuid('model_id');
            $table->string('identifier', 255);
            $table->text('note')->nullable();
            $table->char('status', 1)->default('A')->comment('A=Activo,I=Inactivo,T=Papelera');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('brand_id')->references('id')->on('workshop_brands')->cascadeOnDelete();
            $table->foreign('model_id')->references('id')->on('workshop_models')->cascadeOnDelete();
            $table->unique(['company_id', 'identifier'], 'workshop_equipments_company_identifier_unique');
            $table->index(['company_id', 'brand_id'], 'workshop_equipments_company_brand_index');
            $table->index(['company_id', 'model_id'], 'workshop_equipments_company_model_index');
            $table->index(['company_id', 'status'], 'workshop_equipments_company_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workshop_equipments');
    }
};
