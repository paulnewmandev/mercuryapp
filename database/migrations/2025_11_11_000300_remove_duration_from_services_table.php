<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            if (Schema::hasColumn('services', 'duration_minutes')) {
                $table->dropColumn('duration_minutes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            if (! Schema::hasColumn('services', 'duration_minutes')) {
                $table->integer('duration_minutes')->nullable()->after('description');
            }
        });
    }
};
