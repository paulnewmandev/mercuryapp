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
        Schema::table('invoices', function (Blueprint $table) {
            $table->uuid('salesperson_id')->nullable()->after('customer_id');
            $table->foreign('salesperson_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['salesperson_id'], 'invoices_salesperson_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['salesperson_id']);
            $table->dropIndex('invoices_salesperson_index');
            $table->dropColumn('salesperson_id');
        });
    }
};
