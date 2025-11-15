<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        Schema::table('roles', function (Blueprint $table): void {
            if (! Schema::hasColumn('roles', 'company_id')) {
                $table->uuid('company_id')->nullable()->after('id');
            }

            $table->dropUnique('roles_name_unique');
        });

        Schema::table('roles', function (Blueprint $table): void {
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->cascadeOnDelete();

            $table->index('company_id', 'roles_company_index');
            $table->unique(['company_id', 'name'], 'roles_company_name_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        Schema::table('roles', function (Blueprint $table): void {
            $table->dropUnique('roles_company_name_unique');
            $table->dropIndex('roles_company_index');
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
            $table->unique('name', 'roles_name_unique');
        });
    }
};

