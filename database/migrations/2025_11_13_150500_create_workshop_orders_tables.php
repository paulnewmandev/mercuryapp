<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workshop_accessories', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('name', 255);
            $table->char('status', 1)->default('A')->comment('A=Activo,I=Inactivo,T=Papelera');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->unique(['company_id', 'name'], 'workshop_accessories_company_name_unique');
            $table->index(['company_id', 'status'], 'workshop_accessories_company_status_index');
        });

        Schema::create('workshop_orders', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('category_id');
            $table->uuid('state_id')->nullable();
            $table->uuid('customer_id');
            $table->uuid('equipment_id');
            $table->uuid('responsible_user_id');
            $table->string('priority', 50)->default('Normal');
            $table->string('status_code', 50)->default('Abierta');
            $table->string('work_summary', 255);
            $table->text('work_description')->nullable();
            $table->text('general_condition')->nullable();
            $table->boolean('diagnosis')->default(false);
            $table->boolean('warranty')->default(false);
            $table->string('equipment_password', 255)->nullable();
            $table->dateTime('promised_at')->nullable();
            $table->string('budget_currency', 5)->nullable();
            $table->decimal('budget_amount', 12, 2)->nullable();
            $table->string('advance_currency', 5)->nullable();
            $table->decimal('advance_amount', 12, 2)->nullable();
            $table->text('accessories_notes')->nullable();
            $table->text('delivered_accessories_other')->nullable();
            $table->char('status', 1)->default('A')->comment('A=Activo,I=Inactivo,T=Papelera');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('category_id')->references('id')->on('workshop_categories')->cascadeOnDelete();
            $table->foreign('state_id')->references('id')->on('workshop_states')->nullOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->foreign('equipment_id')->references('id')->on('workshop_equipments')->cascadeOnDelete();
            $table->foreign('responsible_user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['company_id', 'category_id']);
            $table->index(['company_id', 'customer_id']);
            $table->index(['company_id', 'equipment_id']);
            $table->index(['company_id', 'responsible_user_id']);
            $table->index(['company_id', 'status'], 'workshop_orders_company_status_index');
        });

        Schema::create('workshop_order_accessory', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->uuid('order_id');
            $table->uuid('accessory_id');

            $table->primary(['order_id', 'accessory_id'], 'workshop_order_accessory_primary');

            $table->foreign('order_id')->references('id')->on('workshop_orders')->cascadeOnDelete();
            $table->foreign('accessory_id')->references('id')->on('workshop_accessories')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workshop_order_accessory');
        Schema::dropIfExists('workshop_orders');
        Schema::dropIfExists('workshop_accessories');
    }
};
