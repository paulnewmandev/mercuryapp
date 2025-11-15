<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea las tablas relacionadas con finanzas, ingresos y egresos.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('bank_name', 255);
            $table->string('account_number', 255);
            $table->string('account_type', 20);
            $table->string('account_holder_name', 255);
            $table->string('alias', 255)->nullable();
            $table->char('status', 1)->default('A')->comment('A=Activo,I=Inactivo,T=Papelera');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->unique(['company_id', 'bank_name', 'account_number'], 'bank_accounts_company_bank_unique');
            $table->index(['company_id', 'status'], 'bank_accounts_status_index');
        });

        Schema::create('payment_cards', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->char('status', 1)->default('A')->comment('A=Activo,I=Inactivo,T=Papelera');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->index(['company_id', 'status'], 'payment_cards_company_status_index');
        });

        Schema::create('payment_methods', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';
            $table->uuid('id')->primary();
            $table->string('name', 50);
            $table->char('status', 1)->default('A')->comment('A=Activo,I=Inactivo,T=Papelera');
            $table->timestamps();

            $table->index('status', 'payment_methods_status_index');
        });

        Schema::create('income_types', function (Blueprint $table): void {
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
            $table->unique(['company_id', 'code'], 'income_types_company_code_unique');
            $table->index(['company_id', 'status'], 'income_types_status_index');
        });

        Schema::create('expense_types', function (Blueprint $table): void {
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
            $table->unique(['company_id', 'code'], 'expense_types_company_code_unique');
            $table->index(['company_id', 'status'], 'expense_types_status_index');
        });

        Schema::create('checks', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('bank_account_id');
            $table->string('check_type', 20);
            $table->string('check_number', 50);
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->unsignedBigInteger('amount_cents');
            $table->text('description')->nullable();
            $table->string('workflow_status', 20)->default('PENDING');
            $table->char('status', 1)->default('A')->comment('A=Activo,I=Inactivo,T=Papelera');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->restrictOnDelete();
            $table->unique(['bank_account_id', 'check_number'], 'checks_bank_account_number_unique');
            $table->index(['company_id', 'status'], 'checks_company_status_index');
        });
    }

    /**
     * Revierte la migración.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('checks');
        Schema::dropIfExists('expense_types');
        Schema::dropIfExists('income_types');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('payment_cards');
        Schema::dropIfExists('bank_accounts');
    }
};


