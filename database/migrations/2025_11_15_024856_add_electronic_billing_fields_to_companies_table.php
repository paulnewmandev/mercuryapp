<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->text('digital_signature_password')->nullable()
                ->after('digital_signature')
                ->comment('Contraseña del certificado .p12 (encriptada)');
            
            $table->string('sri_environment', 20)->default('development')
                ->after('digital_signature_password')
                ->comment('development = Pruebas, production = Producción');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['digital_signature_password', 'sri_environment']);
        });
    }
};
