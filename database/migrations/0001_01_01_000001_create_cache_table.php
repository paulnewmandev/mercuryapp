<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea las tablas de cache y locks para almacenamiento rápido de datos.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('cache', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';
            $table->string('key', 255)->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';
            $table->string('key', 255)->primary();
            $table->string('owner', 255);
            $table->integer('expiration');
        });
    }

    /**
     * Elimina las tablas relacionadas con cache y bloqueos.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
    }
};
