<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table): void {
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';
                $table->uuid('id')->primary();
                $table->uuid('company_id')->nullable();
                $table->string('name', 120);
                $table->string('display_name', 255);
                $table->text('description')->nullable();
                $table->char('status', 1)->default('A')->comment('A=Activo,I=Inactivo,T=Papelera');
                $table->timestamps();

                $table->foreign('company_id')
                    ->references('id')
                    ->on('companies')
                    ->cascadeOnDelete();

                $table->unique(['company_id', 'name'], 'permissions_company_name_unique');
                $table->index(['company_id', 'status'], 'permissions_company_status_index');
            });
        }

        if (! Schema::hasTable('permission_role')) {
            Schema::create('permission_role', function (Blueprint $table): void {
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';
                $table->uuid('role_id');
                $table->uuid('permission_id');
                $table->timestamps();

                $table->primary(['role_id', 'permission_id'], 'permission_role_primary');

                $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
                $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
    }
};

