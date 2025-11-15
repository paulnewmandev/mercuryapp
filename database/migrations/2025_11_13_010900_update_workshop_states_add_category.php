<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workshop_states', function (Blueprint $table): void {
            if (Schema::hasColumn('workshop_states', 'company_id') && ! Schema::hasColumn('workshop_states', 'category_id')) {
                $table->uuid('category_id')->after('company_id');
                $table->foreign('category_id')->references('id')->on('workshop_categories')->cascadeOnDelete();
            }

            if (Schema::hasColumn('workshop_states', 'company_id')) {
                $table->dropUnique('workshop_states_company_name_unique');
                $table->unique(['category_id', 'name'], 'workshop_states_category_name_unique');
            }

            $table->index(['category_id', 'status'], 'workshop_states_category_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('workshop_states', function (Blueprint $table): void {
            if (Schema::hasColumn('workshop_states', 'category_id')) {
                $table->dropUnique('workshop_states_category_name_unique');
                $table->dropIndex('workshop_states_category_status_index');

                $table->unique(['company_id', 'name'], 'workshop_states_company_name_unique');

                $table->dropForeign(['category_id']);
                $table->dropColumn('category_id');
            }
        });
    }
};


