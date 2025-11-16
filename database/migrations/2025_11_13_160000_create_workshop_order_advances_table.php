<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workshop_order_advances', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('order_id');
            $table->string('currency', 5)->default('USD');
            $table->decimal('amount', 12, 2);
            $table->dateTime('payment_date');
            $table->uuid('payment_method_id')->nullable();
            $table->string('reference', 255)->nullable();
            $table->text('notes')->nullable();
            $table->char('status', 1)->default('A')->comment('A=Activo,I=Inactivo,T=Papelera');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('order_id')->references('id')->on('workshop_orders')->cascadeOnDelete();
            $table->foreign('payment_method_id')->references('id')->on('payment_methods')->nullOnDelete();
            $table->index(['company_id', 'order_id'], 'workshop_order_advances_company_order_index');
            $table->index(['company_id', 'status'], 'workshop_order_advances_company_status_index');
            $table->index('payment_date', 'workshop_order_advances_payment_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workshop_order_advances');
    }
};

