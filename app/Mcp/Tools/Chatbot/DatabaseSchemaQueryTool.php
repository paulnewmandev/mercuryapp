<?php

namespace App\Mcp\Tools\Chatbot;

use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class DatabaseSchemaQueryTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Consulta el esquema de la base de datos leyendo las migraciones de Laravel.
        Proporciona información sobre tablas, columnas, tipos de datos, relaciones y estructura de la base de datos.
        Útil para entender la estructura de la base de datos y responder preguntas sobre cómo están organizados los datos.
        Sinónimos: esquema de base de datos, estructura de tablas, migraciones, modelo de datos, schema.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        if (!Auth::check()) {
            return Response::error('Usuario debe estar autenticado');
        }

        $arguments = $request->all();
        $tableName = $arguments['table_name'] ?? null;
        $includeRelations = $arguments['include_relations'] ?? false;

        $migrationsPath = database_path('migrations');
        $migrationFiles = File::glob($migrationsPath . '/*.php');

        if (empty($migrationFiles)) {
            return Response::text("No se encontraron archivos de migración.");
        }

        $output = "## Esquema de Base de Datos\n\n";
        $output .= "**Total de migraciones:** " . count($migrationFiles) . "\n\n";

        // Si se especifica una tabla, buscar solo esa
        if ($tableName) {
            $output .= "### Tabla: {$tableName}\n\n";
            $found = false;
            
            foreach ($migrationFiles as $file) {
                $content = File::get($file);
                $fileName = basename($file);
                
                // Buscar si esta migración crea o modifica la tabla
                if (preg_match('/Schema::(create|table)\s*\(\s*[\'"]' . preg_quote($tableName, '/') . '[\'"]/', $content)) {
                    $found = true;
                    $output .= "**Migración:** {$fileName}\n\n";
                    $output .= $this->extractTableSchema($content, $tableName);
                    break;
                }
            }
            
            if (!$found) {
                $output .= "No se encontró información sobre la tabla '{$tableName}' en las migraciones.\n";
            }
        } else {
            // Listar todas las tablas
            $tables = [];
            foreach ($migrationFiles as $file) {
                $content = File::get($file);
                
                // Extraer nombres de tablas de create
                if (preg_match_all('/Schema::create\s*\(\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
                    foreach ($matches[1] as $table) {
                        if (!in_array($table, $tables)) {
                            $tables[] = $table;
                        }
                    }
                }
            }
            
            sort($tables);
            
            $output .= "### Tablas en la Base de Datos\n\n";
            $output .= "Total: " . count($tables) . " tablas\n\n";
            
            foreach ($tables as $table) {
                $output .= "- **{$table}**\n";
            }
            
            if ($includeRelations) {
                $output .= "\n### Relaciones Principales\n\n";
                $output .= $this->extractRelations($migrationFiles);
            }
        }

        return Response::text($output);
    }

    private function extractTableSchema(string $content, string $tableName): string
    {
        $output = "";
        
        // Extraer columnas
        if (preg_match('/Schema::(create|table)\s*\(\s*[\'"]' . preg_quote($tableName, '/') . '[\'"]\s*,\s*function\s*\([^)]*\)\s*\{([^}]+)\}/s', $content, $matches)) {
            $tableDefinition = $matches[2];
            
            // Extraer columnas
            if (preg_match_all('/\$table->(\w+)\s*\([\'"]([^\'"]+)[\'"]/', $tableDefinition, $columnMatches)) {
                $output .= "**Columnas:**\n";
                for ($i = 0; $i < count($columnMatches[1]); $i++) {
                    $method = $columnMatches[1][$i];
                    $columnName = $columnMatches[2][$i];
                    $output .= "- `{$columnName}` ({$method})\n";
                }
            }
            
            // Extraer foreign keys
            if (preg_match_all('/\$table->foreign\s*\([\'"]([^\'"]+)[\'"]\)->references/', $tableDefinition, $fkMatches)) {
                $output .= "\n**Foreign Keys:**\n";
                foreach ($fkMatches[1] as $fk) {
                    $output .= "- `{$fk}`\n";
                }
            }
        }
        
        return $output;
    }

    private function extractRelations(array $migrationFiles): string
    {
        $relations = [];
        
        foreach ($migrationFiles as $file) {
            $content = File::get($file);
            
            if (preg_match_all('/\$table->foreign\s*\([\'"]([^\'"]+)[\'"]\)->references\s*\([\'"]([^\'"]+)[\'"]\)->on\s*\([\'"]([^\'"]+)[\'"]\)/', $content, $matches)) {
                for ($i = 0; $i < count($matches[1]); $i++) {
                    $relations[] = [
                        'column' => $matches[1][$i],
                        'references' => $matches[2][$i],
                        'on' => $matches[3][$i],
                    ];
                }
            }
        }
        
        $output = "";
        foreach ($relations as $rel) {
            $output .= "- `{$rel['column']}` → `{$rel['on']}.{$rel['references']}`\n";
        }
        
        return $output ?: "No se encontraron relaciones definidas.\n";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'table_name' => $schema->string()
                ->nullable()
                ->description('Nombre de la tabla específica a consultar (opcional, si no se proporciona lista todas las tablas)'),
            'include_relations' => $schema->boolean()
                ->default(false)
                ->description('Si es true, incluye información sobre relaciones (foreign keys)'),
        ];
    }
}

