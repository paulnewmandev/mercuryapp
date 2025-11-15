<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\DocumentSequence;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DocumentSequenceSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->first();

        if (! $company) {
            $this->command?->warn('No se encontró una compañía para asociar los secuenciales.');
            return;
        }

        $types = config('document_sequences.types', []);

        $defaults = [
            'FACTURA' => [
                'establishment_code' => '001',
                'emission_point_code' => '001',
                'current_sequence' => 1,
            ],
            'ORDEN_DE_TRABAJO' => [
                'establishment_code' => '001',
                'emission_point_code' => '001',
                'current_sequence' => 1,
            ],
            'COTIZACIONES' => [
                'establishment_code' => '001',
                'emission_point_code' => '001',
                'current_sequence' => 1,
            ],
            'NOTA_DE_VENTA' => [
                'establishment_code' => '001',
                'emission_point_code' => '001',
                'current_sequence' => 1,
            ],
        ];

        foreach ($defaults as $type => $settings) {
            $label = $types[$type] ?? $type;

            $sequence = DocumentSequence::query()->firstOrNew([
                'company_id' => $company->id,
                'document_type' => $type,
            ]);
            
            if (!$sequence->exists) {
                $sequence->id = (string) Str::uuid();
            }
            
            $sequence->name = $label;
            $sequence->establishment_code = $settings['establishment_code'];
            $sequence->emission_point_code = $settings['emission_point_code'];
            $sequence->current_sequence = $settings['current_sequence'];
            $sequence->status = 'A';
            $sequence->save();
        }

        $this->command?->info('Secuenciales generados correctamente para la empresa.');
    }
}

