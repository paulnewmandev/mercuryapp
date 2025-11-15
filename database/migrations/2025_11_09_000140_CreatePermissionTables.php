<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla de permisos asociados a roles.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';
            $table->uuid('id')->primary();
            $table->uuid('role_id');
            $table->string('name', 255)->unique();
            $table->text('description')->nullable();
            $table->char('status', 1)->default('A')->comment('A=Activo,I=Inactivo,T=Papelera');
            $table->timestamps();

            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->index(['role_id', 'status'], 'permissions_role_status_index');
        });
    }

    /**
     * Revierte la creación de la tabla.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};


