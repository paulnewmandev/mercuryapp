<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_transfers', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('origin_warehouse_id');
            $table->uuid('destination_warehouse_id');
            $table->date('movement_date');
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->char('status', 1)->default('A')->comment('A=Completado,I=Anulado,T=Papelera');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('origin_warehouse_id')->references('id')->on('warehouses')->restrictOnDelete();
            $table->foreign('destination_warehouse_id')->references('id')->on('warehouses')->restrictOnDelete();
            $table->index(['company_id', 'status'], 'product_transfers_company_status_index');
            $table->index(['company_id', 'movement_date'], 'product_transfers_company_date_index');
        });

        Schema::create('product_transfer_items', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';
            $table->uuid('id')->primary();
            $table->uuid('product_transfer_id');
            $table->uuid('product_id');
            $table->unsignedInteger('quantity');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('product_transfer_id')->references('id')->on('product_transfers')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
            $table->index(['product_transfer_id', 'product_id'], 'transfer_items_transfer_product_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_transfer_items');
        Schema::dropIfExists('product_transfers');
    }
};

