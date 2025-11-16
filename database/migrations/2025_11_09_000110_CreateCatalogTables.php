<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea tablas de catálogos de productos, servicios y listas de precios.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('product_lines', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->char('status', 1)->default('A')->comment('A=Activo,I=Inactivo,T=Papelera');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->index(['company_id', 'status'], 'product_lines_company_status_index');
        });

        Schema::create('product_categories', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('parent_id')->nullable();
            $table->string('name', 255);
            $table->char('status', 1)->default('A')->comment('A=Activo,I=Inactivo,T=Papelera');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('parent_id')->references('id')->on('product_categories')->nullOnDelete();
            $table->index(['company_id', 'status'], 'product_categories_company_status_index');
        });

        Schema::create('service_categories', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('parent_id')->nullable();
            $table->string('name', 255);
            $table->char('status', 1)->default('A')->comment('A=Activo,I=Inactivo,T=Papelera');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('parent_id')->references('id')->on('service_categories')->nullOnDelete();
            $table->index(['company_id', 'status'], 'service_categories_company_status_index');
        });

        Schema::create('services', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('category_id')->nullable();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->char('status', 1)->default('A')->comment('A=Activo,I=Inactivo,T=Papelera');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('category_id')->references('id')->on('service_categories')->nullOnDelete();
            $table->index(['company_id', 'status'], 'services_company_status_index');
        });

        Schema::create('price_lists', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->char('status', 1)->default('A')->comment('A=Activo,I=Inactivo,T=Papelera');
            $table->timestamps();

            $table->index('status', 'price_lists_status_index');
        });

        Schema::create('warehouses', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('name', 255);
            $table->text('address')->nullable();
            $table->char('status', 1)->default('A')->comment('A=Activo,I=Inactivo,T=Papelera');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->index(['company_id', 'status'], 'warehouses_company_status_index');
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('product_line_id')->nullable();
            $table->uuid('category_id')->nullable();
            $table->string('sku', 100);
            $table->string('barcode', 255)->nullable();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('image_url', 512)->nullable();
            $table->boolean('show_in_pos')->default(true);
            $table->boolean('show_in_b2b')->default(true);
            $table->boolean('show_in_b2c')->default(true);
            $table->char('status', 1)->default('A')->comment('A=Activo,I=Inactivo,T=Papelera');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('product_line_id')->references('id')->on('product_lines')->nullOnDelete();
            $table->foreign('category_id')->references('id')->on('product_categories')->nullOnDelete();
            $table->unique(['company_id', 'sku'], 'products_company_sku_unique');
            $table->unique(['company_id', 'barcode'], 'products_company_barcode_unique');
            $table->index(['company_id', 'status'], 'products_company_status_index');
        });

        Schema::create('item_prices', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->uuid('id')->primary();
            $table->uuid('item_id');
            $table->string('item_type', 20);
            $table->uuid('price_list_id');
            $table->decimal('value', 15, 2);
            $table->char('status', 1)->default('A')->comment('A=Activo,I=Inactivo,T=Papelera');
            $table->timestamps();

            $table->foreign('price_list_id')->references('id')->on('price_lists')->cascadeOnDelete();
            $table->index(['item_id', 'item_type'], 'item_prices_item_index');
            $table->index(['status', 'price_list_id'], 'item_prices_status_index');
        });

        Schema::create('product_stock', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->uuid('product_id');
            $table->uuid('warehouse_id');
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('minimum_stock')->default(0);
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->primary(['product_id', 'warehouse_id']);
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
        });
    }

    /**
     * Elimina las tablas creadas por la migración.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('product_stock');
        Schema::dropIfExists('item_prices');
        Schema::dropIfExists('products');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('price_lists');
        Schema::dropIfExists('services');
        Schema::dropIfExists('service_categories');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('product_lines');
    }
};


