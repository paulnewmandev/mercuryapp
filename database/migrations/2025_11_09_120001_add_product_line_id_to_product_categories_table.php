<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_categories', function (Blueprint $table): void {
            if (! Schema::hasColumn('product_categories', 'product_line_id')) {
                $table->uuid('product_line_id')->nullable()->after('company_id');
                $table->foreign('product_line_id')->references('id')->on('product_lines')->nullOnDelete();
                $table->index(['company_id', 'product_line_id'], 'product_categories_company_line_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_categories', function (Blueprint $table): void {
            if (Schema::hasColumn('product_categories', 'product_line_id')) {
                $table->dropForeign(['product_line_id']);
                $table->dropIndex('product_categories_company_line_index');
                $table->dropColumn('product_line_id');
            }
        });
    }
};
