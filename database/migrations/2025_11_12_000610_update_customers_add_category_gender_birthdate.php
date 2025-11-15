<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            if (! Schema::hasColumn('customers', 'category_id')) {
                $table->uuid('category_id')->nullable()->after('company_id');
                $table->foreign('category_id')->references('id')->on('customer_categories')->nullOnDelete();
                $table->index(['company_id', 'category_id'], 'customers_company_category_index');
            }

            if (! Schema::hasColumn('customers', 'sex')) {
                $table->string('sex', 10)->nullable()->after('business_name');
            }

            if (! Schema::hasColumn('customers', 'birth_date')) {
                $table->date('birth_date')->nullable()->after('sex');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            if (Schema::hasColumn('customers', 'category_id')) {
                $table->dropForeign(['category_id']);
                $table->dropIndex('customers_company_category_index');
                $table->dropColumn('category_id');
            }

            if (Schema::hasColumn('customers', 'sex')) {
                $table->dropColumn('sex');
            }

            if (Schema::hasColumn('customers', 'birth_date')) {
                $table->dropColumn('birth_date');
            }
        });
    }
};

