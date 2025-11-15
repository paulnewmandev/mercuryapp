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
        // Verificar el estado actual de la tabla
        $hasCompanyId = Schema::hasColumn('document_sequences', 'company_id');
        $hasBranchId = Schema::hasColumn('document_sequences', 'branch_id');

        // Si ya tiene company_id y no tiene branch_id, solo asegurar las foreign keys e índices
        if ($hasCompanyId && !$hasBranchId) {
            // Verificar si ya existen los índices y foreign keys
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'document_sequences'
                AND COLUMN_NAME = 'company_id'
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            
            $hasFk = !empty($foreignKeys);
            
            // Actualizar company_id NULL con la primera compañía disponible
            $company = DB::table('companies')->first();
            if ($company) {
                DB::statement('UPDATE document_sequences SET company_id = ? WHERE company_id IS NULL', [$company->id]);
            }
            
            // Eliminar registros con company_id inválido
            DB::statement('
                DELETE ds FROM document_sequences ds
                LEFT JOIN companies c ON ds.company_id = c.id
                WHERE c.id IS NULL
            ');
            
            DB::statement('DELETE FROM document_sequences WHERE company_id IS NULL');
            
            if (!$hasFk) {
                // Verificar si hay valores inválidos antes de agregar la foreign key
                $invalidCount = DB::selectOne('
                    SELECT COUNT(*) as count
                    FROM document_sequences ds
                    LEFT JOIN companies c ON ds.company_id = c.id
                    WHERE c.id IS NULL
                ');
                
                if ($invalidCount && $invalidCount->count > 0) {
                    // Eliminar los inválidos
                    DB::statement('
                        DELETE ds FROM document_sequences ds
                        LEFT JOIN companies c ON ds.company_id = c.id
                        WHERE c.id IS NULL
                    ');
                }
                
                Schema::table('document_sequences', function (Blueprint $table): void {
                    // Cambiar a NOT NULL
                    $table->uuid('company_id')->nullable(false)->change();
                    
                    // Agregar foreign key, índices y unique constraint
                    $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
                    $table->unique(['company_id', 'document_type'], 'document_sequences_company_type_unique');
                    $table->index(['status', 'company_id'], 'document_sequences_status_index');
                });
            }
            return;
        }

        // Si tiene branch_id, hacer la migración completa
        if ($hasBranchId) {
            // Primero, agregar la columna company_id temporalmente solo si no existe
            if (!$hasCompanyId) {
                Schema::table('document_sequences', function (Blueprint $table): void {
                    $table->uuid('company_id')->nullable()->after('id');
                });
            }

            // Actualizar los datos existentes: migrar branch_id a company_id
            DB::statement('
                UPDATE document_sequences ds
                INNER JOIN branches b ON ds.branch_id = b.id
                SET ds.company_id = b.company_id
                WHERE ds.company_id IS NULL
            ');
            
            // Eliminar los registros que no pudieron ser actualizados (sin branch asociado)
            DB::statement('DELETE FROM document_sequences WHERE company_id IS NULL');
        }

        Schema::table('document_sequences', function (Blueprint $table): void {
            // Eliminar índices y foreign key existentes solo si branch_id existe
            if ($hasBranchId) {
                // Verificar si la foreign key existe antes de eliminarla
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'document_sequences'
                    AND COLUMN_NAME = 'branch_id'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                foreach ($foreignKeys as $fk) {
                    try {
                        $table->dropForeign([$fk->CONSTRAINT_NAME]);
                    } catch (\Exception $e) {
                        // Ignorar si no existe
                    }
                }
                
                // Eliminar índices y unique constraints
                try {
                    $table->dropUnique('document_sequences_branch_type_unique');
                } catch (\Exception $e) {
                    // Ignorar si no existe
                }
                
                try {
                    $table->dropIndex('document_sequences_status_index');
                } catch (\Exception $e) {
                    // Ignorar si no existe
                }
                
                // Eliminar columna branch_id
                $table->dropColumn('branch_id');
            }
            
            // Eliminar registros con company_id NULL si quedan
            DB::statement('DELETE FROM document_sequences WHERE company_id IS NULL');
            
            // Verificar si la foreign key ya existe
            $existingFk = DB::selectOne("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'document_sequences'
                AND COLUMN_NAME = 'company_id'
                AND REFERENCED_TABLE_NAME = 'companies'
            ");
            
            if (!$existingFk) {
                // Cambiar a NOT NULL si es nullable
                $table->uuid('company_id')->nullable(false)->change();
                
                // Agregar foreign key, índices y unique constraint
                $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
                $table->unique(['company_id', 'document_type'], 'document_sequences_company_type_unique');
                $table->index(['status', 'company_id'], 'document_sequences_status_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_sequences', function (Blueprint $table): void {
            // Eliminar índices y foreign key nuevos
            $table->dropForeign(['company_id']);
            $table->dropUnique('document_sequences_company_type_unique');
            $table->dropIndex('document_sequences_status_index');
            
            // Eliminar columna company_id
            $table->dropColumn('company_id');
            
            // Agregar columna branch_id
            $table->uuid('branch_id')->after('id');
            
            // Agregar foreign key, índices y unique constraint originales
            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            $table->unique(['branch_id', 'document_type'], 'document_sequences_branch_type_unique');
            $table->index(['status', 'branch_id'], 'document_sequences_status_index');
        });
    }
};
