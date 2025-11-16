<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'gender')) {
            return;
        }

        // Cambiar el enum para incluir 'OTHER'
        DB::statement("ALTER TABLE `users` MODIFY `gender` ENUM('MALE', 'FEMALE', 'OTHER') NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'gender')) {
            return;
        }

        // Revertir al enum original
        DB::statement("ALTER TABLE `users` MODIFY `gender` ENUM('MALE', 'FEMALE') NULL");
    }
};
