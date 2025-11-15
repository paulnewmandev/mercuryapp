<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['company_id']);
        });

        DB::statement('ALTER TABLE `users` MODIFY `company_id` CHAR(36) NULL');

        Schema::table('users', function (Blueprint $table): void {
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['company_id']);
        });

        DB::statement('ALTER TABLE `users` MODIFY `company_id` CHAR(36) NOT NULL');

        Schema::table('users', function (Blueprint $table): void {
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->cascadeOnDelete();
        });
    }
};

