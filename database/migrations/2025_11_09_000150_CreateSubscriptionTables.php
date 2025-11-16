<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla de planes de suscripción disponibles en MercuryApp.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->uuid('id')->primary();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->decimal('price_month', 10, 2)->default(0);
            $table->decimal('price_year', 10, 2)->default(0);
            $table->json('features')->nullable();
            $table->char('status', 1)->default('A')->comment('A=Activo,I=Inactivo,T=Papelera');
            $table->timestamps();

            $table->index('status', 'subscription_plans_status_index');
        });
    }

    /**
     * Revierte la migración ejecutada.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};


