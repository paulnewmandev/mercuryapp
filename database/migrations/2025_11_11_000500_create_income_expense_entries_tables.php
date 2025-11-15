<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incomes', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('income_type_id');
            $table->date('movement_date');
            $table->string('concept', 255);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('amount_cents');
            $table->string('currency_code', 3)->default('USD');
            $table->string('reference', 100)->nullable();
            $table->char('status', 1)->default('A')->comment('A=Activo,I=Inactivo,T=Papelera');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('income_type_id')->references('id')->on('income_types')->restrictOnDelete();
            $table->index(['company_id', 'movement_date'], 'incomes_company_date_index');
            $table->index(['company_id', 'status'], 'incomes_company_status_index');
        });

        Schema::create('expenses', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('expense_type_id');
            $table->date('movement_date');
            $table->string('concept', 255);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('amount_cents');
            $table->string('currency_code', 3)->default('USD');
            $table->string('reference', 100)->nullable();
            $table->char('status', 1)->default('A')->comment('A=Activo,I=Inactivo,T=Papelera');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('expense_type_id')->references('id')->on('expense_types')->restrictOnDelete();
            $table->index(['company_id', 'movement_date'], 'expenses_company_date_index');
            $table->index(['company_id', 'status'], 'expenses_company_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('incomes');
    }
};


