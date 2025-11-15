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
        Schema::table('invoices', function (Blueprint $table) {
            // Clave de acceso (generada del XML)
            $table->string('access_key', 49)->nullable()->after('invoice_number')
                ->comment('Clave de acceso única del documento electrónico');
            
            // Estado en el SRI
            $table->string('sri_status', 20)->nullable()->after('access_key')
                ->comment('draft, received, authorized, rejected, cancelled');
            
            // Número de autorización del SRI
            $table->string('authorization_number', 49)->nullable()->after('sri_status')
                ->comment('Número de autorización del SRI');
            
            // Fecha de autorización
            $table->timestamp('authorized_at')->nullable()->after('authorization_number')
                ->comment('Fecha y hora de autorización del SRI');
            
            // XML firmado (almacenado como texto)
            $table->longText('xml_signed')->nullable()->after('authorized_at')
                ->comment('XML firmado del documento electrónico');
            
            // XML autorizado (respuesta del SRI)
            $table->longText('xml_authorized')->nullable()->after('xml_signed')
                ->comment('XML autorizado recibido del SRI');
            
            // Mensajes de error del SRI
            $table->text('sri_errors')->nullable()->after('xml_authorized')
                ->comment('Mensajes de error del SRI (JSON)');
            
            // Ambiente SRI usado
            $table->string('sri_environment', 20)->nullable()->after('sri_errors')
                ->comment('development o production');
            
            // Índices
            $table->index('access_key', 'invoices_access_key_index');
            $table->index('sri_status', 'invoices_sri_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_access_key_index');
            $table->dropIndex('invoices_sri_status_index');
            $table->dropColumn([
                'access_key',
                'sri_status',
                'authorization_number',
                'authorized_at',
                'xml_signed',
                'xml_authorized',
                'sri_errors',
                'sri_environment',
            ]);
        });
    }
};
