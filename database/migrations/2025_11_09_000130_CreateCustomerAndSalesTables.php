<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea tablas vinculadas a clientes, órdenes de servicio e ingresos.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('customer_type', 20);
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('business_name', 255)->nullable();
            $table->string('document_type', 20)->default('NONE');
            $table->string('document_number', 50)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('phone_number', 50)->nullable();
            $table->text('address')->nullable();
            $table->char('status', 1)->default('A')->comment('A=Activo,I=Inactivo,T=Papelera');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->unique(['company_id', 'email'], 'customers_company_email_unique');
            $table->index(['company_id', 'status'], 'customers_company_status_index');
        });

        Schema::create('service_orders', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('branch_id');
            $table->uuid('customer_id')->nullable();
            $table->uuid('technician_id')->nullable();
            $table->string('order_number', 50);
            $table->string('device_description', 255);
            $table->string('device_serial_number', 100)->nullable();
            $table->text('reported_issue')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('work_performed')->nullable();
            $table->string('workflow_status', 30)->default('pending');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('total_paid', 15, 2)->default(0);
            $table->decimal('balance_due', 15, 2)->storedAs('total_amount - total_paid');
            $table->text('notes')->nullable();
            $table->char('status', 1)->default('A')->comment('A=Activo,I=Inactivo,T=Papelera');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->restrictOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->foreign('technician_id')->references('id')->on('users')->nullOnDelete();
            $table->unique(['branch_id', 'order_number'], 'service_orders_branch_number_unique');
            $table->index(['company_id', 'workflow_status'], 'service_orders_company_status_index');
        });

        Schema::create('service_order_items', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';
            $table->uuid('service_order_id');
            $table->uuid('item_id');
            $table->string('item_type', 20);
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('subtotal', 15, 2)->storedAs('quantity * unit_price');
            $table->timestamps();

            $table->primary(['service_order_id', 'item_id', 'item_type'], 'service_order_items_pk');
            $table->foreign('service_order_id')->references('id')->on('service_orders')->cascadeOnDelete();
        });

        Schema::create('invoices', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('branch_id');
            $table->uuid('customer_id');
            $table->string('invoice_number', 50);
            $table->string('source', 30);
            $table->uuid('source_id')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('total_paid', 15, 2)->default(0);
            $table->decimal('balance_due', 15, 2)->storedAs('total_amount - total_paid');
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->string('workflow_status', 30)->default('draft');
            $table->text('notes')->nullable();
            $table->char('status', 1)->default('A')->comment('A=Activo,I=Inactivo,T=Papelera');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->restrictOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->restrictOnDelete();
            $table->unique(['branch_id', 'invoice_number'], 'invoices_branch_number_unique');
            $table->index(['company_id', 'workflow_status'], 'invoices_company_status_index');
        });

        Schema::create('invoice_items', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';
            $table->uuid('invoice_id');
            $table->uuid('item_id');
            $table->string('item_type', 20);
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('subtotal', 15, 2)->storedAs('quantity * unit_price');
            $table->timestamps();

            $table->primary(['invoice_id', 'item_id', 'item_type'], 'invoice_items_pk');
            $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
        });

        Schema::create('invoice_payments', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';
            $table->uuid('id')->primary();
            $table->uuid('invoice_id');
            $table->uuid('payment_method_id');
            $table->decimal('amount', 15, 2);
            $table->timestamp('payment_date')->useCurrent();
            $table->string('reference', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
            $table->foreign('payment_method_id')->references('id')->on('payment_methods')->restrictOnDelete();
            $table->index(['invoice_id', 'payment_method_id'], 'invoice_payments_invoice_index');
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('user_id')->nullable();
            $table->string('description', 256);
            $table->string('title', 120)->nullable();
            $table->string('category', 60)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->char('status', 1)->default('A')->comment('A=Activo,I=Inactivo,T=Papelera');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['company_id', 'created_at'], 'notifications_company_created_index');
            $table->index(['user_id', 'read_at'], 'notifications_user_read_index');
        });
    }

    /**
     * Elimina las tablas creadas en la migración.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('invoice_payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('service_order_items');
        Schema::dropIfExists('service_orders');
        Schema::dropIfExists('customers');
    }
};


