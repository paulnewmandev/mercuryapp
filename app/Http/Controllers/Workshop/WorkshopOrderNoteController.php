<?php

namespace App\Http\Controllers\Workshop;

use App\Http\Controllers\Controller;
use App\Models\WorkshopOrder;
use App\Models\WorkshopOrderNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WorkshopOrderNoteController extends Controller
{
    public function index(WorkshopOrder $workshopOrder): JsonResponse
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        if (! $companyId) {
            $company = \App\Models\Company::query()->first();
            $companyId = $company?->id;
        }

        if ($companyId && $workshopOrder->company_id !== $companyId) {
            abort(404);
        }

        $notes = $workshopOrder->notes()
            ->with('user:id,first_name,last_name,email')
            ->where('status', 'A')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => gettext('Notas obtenidas correctamente.'),
            'data' => [
                'items' => $notes->map(fn (WorkshopOrderNote $note) => [
                    'id' => $note->id,
                    'note' => $note->note,
                    'user' => [
                        'id' => $note->user->id,
                        'name' => $note->user->display_name,
                        'email' => $note->user->email,
                    ],
                    'created_at' => $note->created_at?->toIso8601String(),
                    'created_at_formatted' => $note->created_at?->translatedFormat('d M Y H:i'),
                ]),
            ],
        ]);
    }

    public function store(Request $request, WorkshopOrder $workshopOrder): JsonResponse
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        if (! $companyId) {
            $company = \App\Models\Company::query()->first();
            $companyId = $company?->id;
        }

        if ($companyId && $workshopOrder->company_id !== $companyId) {
            abort(404);
        }

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:5000'],
        ]);

        // Asegurar que company_id siempre tenga un valor válido
        $finalCompanyId = $workshopOrder->company_id ?? $companyId;
        if (! $finalCompanyId) {
            return response()->json([
                'status' => 'error',
                'message' => gettext('No se pudo determinar la compañía para la nota.'),
            ], Response::HTTP_BAD_REQUEST);
        }

        $note = new WorkshopOrderNote($validated);
        $note->order_id = $workshopOrder->id;
        $note->company_id = $finalCompanyId;
        $note->user_id = $user->id;
        $note->status = 'A';
        $note->save();

        $note->load('user:id,first_name,last_name,email');

        return response()->json([
            'status' => 'success',
            'message' => gettext('La nota se agregó correctamente.'),
            'data' => [
                'item' => [
                    'id' => $note->id,
                    'note' => $note->note,
                    'user' => [
                        'id' => $note->user->id,
                        'name' => $note->user->display_name,
                        'email' => $note->user->email,
                    ],
                    'created_at' => $note->created_at?->toIso8601String(),
                    'created_at_formatted' => $note->created_at?->translatedFormat('d M Y H:i'),
                ],
            ],
        ], Response::HTTP_CREATED);
    }

    public function update(Request $request, WorkshopOrder $workshopOrder, WorkshopOrderNote $note): JsonResponse
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        if (! $companyId) {
            $company = \App\Models\Company::query()->first();
            $companyId = $company?->id;
        }

        if ($companyId && $workshopOrder->company_id !== $companyId) {
            abort(404);
        }

        if ($note->order_id !== $workshopOrder->id) {
            abort(404);
        }

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:5000'],
        ]);

        $note->fill($validated);
        $note->save();

        $note->load('user:id,first_name,last_name,email');

        return response()->json([
            'status' => 'success',
            'message' => gettext('La nota se actualizó correctamente.'),
            'data' => [
                'item' => [
                    'id' => $note->id,
                    'note' => $note->note,
                    'user' => [
                        'id' => $note->user->id,
                        'name' => $note->user->display_name,
                        'email' => $note->user->email,
                    ],
                    'created_at' => $note->created_at?->toIso8601String(),
                    'created_at_formatted' => $note->created_at?->translatedFormat('d M Y H:i'),
                ],
            ],
        ]);
    }

    public function destroy(WorkshopOrder $workshopOrder, WorkshopOrderNote $note): JsonResponse
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        if (! $companyId) {
            $company = \App\Models\Company::query()->first();
            $companyId = $company?->id;
        }

        if ($companyId && $workshopOrder->company_id !== $companyId) {
            abort(404);
        }

        if ($note->order_id !== $workshopOrder->id) {
            abort(404);
        }

        $note->status = 'T';
        $note->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('La nota se eliminó correctamente.'),
            'data' => [
                'deleted_id' => $note->id,
            ],
        ]);
    }
}
