<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (! Schema::hasColumn('products', 'subcategory_id')) {
                $table->uuid('subcategory_id')->nullable()->after('category_id');
            }

            if (! Schema::hasColumn('products', 'warehouse_id')) {
                $table->uuid('warehouse_id')->nullable()->after('subcategory_id');
            }

            if (! Schema::hasColumn('products', 'featured_image_path')) {
                $table->string('featured_image_path', 512)->nullable()->after('image_url');
            }

            if (! Schema::hasColumn('products', 'gallery_images')) {
                $table->json('gallery_images')->nullable()->after('featured_image_path');
            }

            if (! Schema::hasColumn('products', 'rating')) {
                $table->unsignedTinyInteger('rating')->default(5)->after('show_in_b2c');
            }

            if (! Schema::hasColumn('products', 'price_list_pos_id')) {
                $table->uuid('price_list_pos_id')->nullable()->after('rating');
            }

            if (! Schema::hasColumn('products', 'price_list_b2c_id')) {
                $table->uuid('price_list_b2c_id')->nullable()->after('price_list_pos_id');
            }

            if (! Schema::hasColumn('products', 'price_list_b2b_id')) {
                $table->uuid('price_list_b2b_id')->nullable()->after('price_list_b2c_id');
            }

            if (Schema::hasColumn('products', 'description')) {
                $table->longText('description')->nullable()->change();
            }
        });

        Schema::table('products', function (Blueprint $table): void {
            if (Schema::hasColumn('products', 'subcategory_id')) {
                $table->foreign('subcategory_id')->references('id')->on('product_categories')->nullOnDelete();
            }

            if (Schema::hasColumn('products', 'warehouse_id')) {
                $table->foreign('warehouse_id')->references('id')->on('warehouses')->nullOnDelete();
            }

            foreach (['price_list_pos_id', 'price_list_b2c_id', 'price_list_b2b_id'] as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->foreign($column)->references('id')->on('price_lists')->nullOnDelete();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            foreach (['price_list_pos_id', 'price_list_b2c_id', 'price_list_b2b_id'] as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropForeign([$column]);
                }
            }

            if (Schema::hasColumn('products', 'warehouse_id')) {
                $table->dropForeign(['warehouse_id']);
            }

            if (Schema::hasColumn('products', 'subcategory_id')) {
                $table->dropForeign(['subcategory_id']);
            }
        });

        Schema::table('products', function (Blueprint $table): void {
            if (Schema::hasColumn('products', 'price_list_b2b_id')) {
                $table->dropColumn('price_list_b2b_id');
            }

            if (Schema::hasColumn('products', 'price_list_b2c_id')) {
                $table->dropColumn('price_list_b2c_id');
            }

            if (Schema::hasColumn('products', 'price_list_pos_id')) {
                $table->dropColumn('price_list_pos_id');
            }

            if (Schema::hasColumn('products', 'rating')) {
                $table->dropColumn('rating');
            }

            if (Schema::hasColumn('products', 'gallery_images')) {
                $table->dropColumn('gallery_images');
            }

            if (Schema::hasColumn('products', 'featured_image_path')) {
                $table->dropColumn('featured_image_path');
            }

            if (Schema::hasColumn('products', 'warehouse_id')) {
                $table->dropColumn('warehouse_id');
            }

            if (Schema::hasColumn('products', 'subcategory_id')) {
                $table->dropColumn('subcategory_id');
            }
        });
    }
};

