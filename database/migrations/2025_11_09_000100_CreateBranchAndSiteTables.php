<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea las tablas relacionadas con sedes y presencia digital de las compañías.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('code', 50);
            $table->string('name', 255);
            $table->text('address')->nullable();
            $table->string('website', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('phone_number', 50)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->char('status', 1)->default('A')->comment('A=Activo,I=Inactivo,T=Papelera');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->unique(['company_id', 'code'], 'branches_company_code_unique');
            $table->index(['status', 'company_id'], 'branches_status_index');
        });

        Schema::create('document_sequences', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';
            $table->uuid('id')->primary();
            $table->uuid('branch_id');
            $table->string('name', 255);
            $table->string('document_type', 50);
            $table->string('establishment_code', 5);
            $table->string('emission_point_code', 5);
            $table->unsignedBigInteger('current_sequence')->default(0);
            $table->char('status', 1)->default('A')->comment('A=Activo,I=Inactivo,T=Papelera');
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            $table->unique(['branch_id', 'document_type'], 'document_sequences_branch_type_unique');
            $table->index(['status', 'branch_id'], 'document_sequences_status_index');
        });

        Schema::create('company_sites', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('subdomain', 100)->unique();
            $table->string('custom_domain', 255)->nullable()->unique();
            $table->char('status', 1)->default('A')->comment('A=Activo,I=Inactivo,T=Papelera');
            $table->json('branding')->nullable();
            $table->json('seo')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->index(['company_id', 'status'], 'company_sites_company_status_index');
        });

        Schema::create('company_site_channels', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';
            $table->uuid('id')->primary();
            $table->uuid('site_id');
            $table->string('channel', 50);
            $table->boolean('enabled')->default(true);
            $table->string('path_prefix', 150)->default('/');
            $table->string('home_slug', 150)->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->foreign('site_id')->references('id')->on('company_sites')->cascadeOnDelete();
            $table->unique(['site_id', 'channel'], 'company_site_channels_unique');
            $table->index('channel', 'company_site_channels_channel_index');
        });
    }

    /**
     * Revierte la creación de tablas.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('company_site_channels');
        Schema::dropIfExists('company_sites');
        Schema::dropIfExists('document_sequences');
        Schema::dropIfExists('branches');
    }
};


