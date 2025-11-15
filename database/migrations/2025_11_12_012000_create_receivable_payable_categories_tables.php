<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('receivable_categories', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('code', 50);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->char('status', 1)->default('A')->comment('A=Activo,I=Inactivo,T=Papelera');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->unique(['company_id', 'code'], 'receivable_categories_company_code_unique');
            $table->index(['company_id', 'status'], 'receivable_categories_company_status_index');
        });

        Schema::create('payable_categories', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('code', 50);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->char('status', 1)->default('A')->comment('A=Activo,I=Inactivo,T=Papelera');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->unique(['company_id', 'code'], 'payable_categories_company_code_unique');
            $table->index(['company_id', 'status'], 'payable_categories_company_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payable_categories');
        Schema::dropIfExists('receivable_categories');
    }
};

