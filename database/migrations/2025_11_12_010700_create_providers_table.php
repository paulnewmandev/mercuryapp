<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('providers', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';

            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('provider_type', 20);
            $table->string('identification_type', 20);
            $table->string('identification_number', 30);
            $table->string('first_name', 120)->nullable();
            $table->string('last_name', 120)->nullable();
            $table->string('business_name', 255)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('phone_number', 50)->nullable();
            $table->char('status', 1)->default('A')->comment('A=Activo,I=Inactivo,T=Papelera');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();

            $table->unique(['company_id', 'identification_number'], 'providers_company_identification_unique');
            $table->index(['company_id', 'status'], 'providers_company_status_index');
            $table->index(['company_id', 'provider_type'], 'providers_company_type_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};

