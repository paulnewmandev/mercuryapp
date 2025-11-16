<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('receivable_entries', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('receivable_category_id');
            $table->date('movement_date');
            $table->string('concept', 255);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('amount_cents');
            $table->string('currency_code', 3)->default('USD');
            $table->string('reference', 100)->nullable();
            $table->boolean('is_collected')->default(false);
            $table->timestamp('collected_at')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('receivable_category_id')->references('id')->on('receivable_categories')->restrictOnDelete();

            $table->index(['company_id', 'movement_date'], 'receivable_entries_company_date_index');
            $table->index(['company_id', 'is_collected'], 'receivable_entries_company_collected_index');
        });

        Schema::create('payable_entries', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('payable_category_id');
            $table->date('movement_date');
            $table->string('concept', 255);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('amount_cents');
            $table->string('currency_code', 3)->default('USD');
            $table->string('reference', 100)->nullable();
            $table->boolean('is_paid')->default(false);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('payable_category_id')->references('id')->on('payable_categories')->restrictOnDelete();

            $table->index(['company_id', 'movement_date'], 'payable_entries_company_date_index');
            $table->index(['company_id', 'is_paid'], 'payable_entries_company_paid_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payable_entries');
        Schema::dropIfExists('receivable_entries');
    }
};

