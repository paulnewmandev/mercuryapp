<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Actualiza la tabla de notificaciones para soportar títulos, categorías y borrado lógico.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            if (! Schema::hasColumn('notifications', 'title')) {
                $table->string('title', 120)->nullable()->after('user_id');
            }

            if (! Schema::hasColumn('notifications', 'category')) {
                $table->string('category', 60)->nullable()->after('title');
            }

            if (! Schema::hasColumn('notifications', 'meta')) {
                $table->json('meta')->nullable()->after('description');
            }

            if (! Schema::hasColumn('notifications', 'deleted_at')) {
                $table->timestamp('deleted_at')->nullable()->after('read_at');
            }
        });
    }

    /**
     * Revierte los cambios aplicados a la tabla de notificaciones.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            if (Schema::hasColumn('notifications', 'deleted_at')) {
                $table->dropColumn('deleted_at');
            }

            if (Schema::hasColumn('notifications', 'meta')) {
                $table->dropColumn('meta');
            }

            if (Schema::hasColumn('notifications', 'category')) {
                $table->dropColumn('category');
            }

            if (Schema::hasColumn('notifications', 'title')) {
                $table->dropColumn('title');
            }
        });
    }
};

