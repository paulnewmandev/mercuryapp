<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('workshop_orders', function (Blueprint $table): void {
            $table->uuid('branch_id')->nullable()->after('company_id');
            $table->string('order_number', 50)->nullable()->after('branch_id');
            
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            $table->index(['company_id', 'branch_id'], 'workshop_orders_company_branch_index');
            $table->index(['branch_id', 'order_number'], 'workshop_orders_branch_number_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workshop_orders', function (Blueprint $table): void {
            $table->dropForeign(['branch_id']);
            $table->dropIndex('workshop_orders_company_branch_index');
            $table->dropIndex('workshop_orders_branch_number_index');
            $table->dropColumn(['branch_id', 'order_number']);
        });
    }
};
