<?php

namespace App\Mcp\Tools\Chatbot;

use App\Models\WorkshopEquipment;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class EquipmentLabelTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Obtiene el enlace PDF de la etiqueta de código de barras de un equipo. Requiere el identificador del equipo.
        Sinónimos: etiqueta del equipo, etiqueta PDF equipo, código de barras equipo, label equipo, imprimir etiqueta equipo.
        Ejemplos: "dame la etiqueta del equipo ABC123", "muéstrame la etiqueta PDF del equipo con identificador XYZ789".
    MARKDOWN;

    public function handle(Request $request): Response
    {
        if (!Auth::check()) {
            return Response::error('Usuario debe estar autenticado');
        }

        $arguments = $request->all();

        $user = Auth::user();
        $companyId = $user->company_id;

        if (!$companyId) {
            $companyId = \App\Models\Company::query()->first()?->id;
        }

        if (!$companyId) {
            return Response::error('No se encontró una compañía asociada');
        }

        $identifier = $arguments['identifier'] ?? null;

        if (!$identifier) {
            return Response::text('<div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"><p class="text-red-800 dark:text-red-200">Se requiere el identificador del equipo.</p></div>');
        }

        $equipment = WorkshopEquipment::where('company_id', $companyId)
            ->where('identifier', $identifier)
            ->with(['brand', 'model'])
            ->first();

        if (!$equipment) {
            return Response::text('<div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg"><p class="text-yellow-800 dark:text-yellow-200">No se encontró un equipo con el identificador: ' . htmlspecialchars($identifier) . '</p></div>');
        }

        if (empty($equipment->identifier)) {
            return Response::text('<div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg"><p class="text-yellow-800 dark:text-yellow-200">El equipo no tiene identificador configurado, por lo que no se puede generar la etiqueta.</p></div>');
        }

        $labelUrl = route('taller.equipos.barcode', $equipment->id);

        $displayName = collect([
            $equipment->brand?->name,
            $equipment->model?->name,
        ])->filter()->implode(' · ');

        if ($displayName === '') {
            $displayName = $equipment->identifier;
        }

        $output = '<div class="space-y-4">';
        $output .= '<div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">';
        $output .= '<p class="text-green-800 dark:text-green-200 font-semibold mb-2">✓ Etiqueta encontrada</p>';
        $output .= '</div>';
        
        $output .= '<div class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-3">';
        $output .= '<h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Equipo</h3>';
        $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[120px]">Identificador:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($equipment->identifier) . '</span></div>';
        if ($displayName !== $equipment->identifier) {
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[120px]">Marca/Modelo:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($displayName) . '</span></div>';
        }
        $output .= '<div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">';
        $output .= '<a href="' . htmlspecialchars($labelUrl) . '" target="_blank" class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-strong">';
        $output .= '<i class="fa-solid fa-file-pdf"></i>';
        $output .= 'Ver Etiqueta PDF';
        $output .= '</a>';
        $output .= '</div>';
        $output .= '</div></div>';

        return Response::text($output);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'identifier' => $schema->string()
                ->description('Identificador del equipo'),
        ];
    }
}

