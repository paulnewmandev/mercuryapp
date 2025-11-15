<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('price_lists', function (Blueprint $table): void {
            if (! Schema::hasColumn('price_lists', 'company_id')) {
                $table->uuid('company_id')->nullable()->after('id');
                $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
                $table->index(['company_id', 'status'], 'price_lists_company_status_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('price_lists', function (Blueprint $table): void {
            if (Schema::hasColumn('price_lists', 'company_id')) {
                $table->dropForeign(['company_id']);
                $table->dropIndex('price_lists_company_status_index');
                $table->dropColumn('company_id');
            }
        });
    }
};
