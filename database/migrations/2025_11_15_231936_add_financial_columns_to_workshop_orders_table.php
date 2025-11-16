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
            $table->decimal('total_cost', 15, 2)->default(0)->after('advance_amount');
            $table->decimal('total_paid', 15, 2)->default(0)->after('total_cost');
            $table->decimal('balance', 15, 2)->default(0)->after('total_paid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workshop_orders', function (Blueprint $table): void {
            $table->dropColumn(['total_cost', 'total_paid', 'balance']);
        });
    }
};
