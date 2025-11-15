<?php

namespace App\Http\Controllers\Workshop;

use App\Contracts\SeoMetaManagerContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workshop\Equipments\StoreWorkshopEquipmentRequest;
use App\Http\Requests\Workshop\Equipments\UpdateWorkshopEquipmentRequest;
use App\Models\WorkshopBrand;
use App\Models\WorkshopEquipment;
use App\Models\WorkshopModel;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Picqer\Barcode\BarcodeGeneratorPNG;

class WorkshopEquipmentController extends Controller
{
    public function __construct(private readonly SeoMetaManagerContract $seoMetaManager)
    {
    }

    public function index(): View
    {
        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Equipos de taller',
            'description' => 'Administra los equipos asociados a marcas y modelos del taller.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Taller')],
            ['label' => gettext('Equipos')],
        ];

        $brandOptions = WorkshopBrand::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('Workshop.Equipments', [
            'meta' => $meta,
            'breadcrumbItems' => $breadcrumbItems,
            'brandOptions' => $brandOptions,
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $sortBy = $request->string('sort_by', 'identifier')->toString();
        $sortDirection = Str::lower($request->string('sort_direction', 'asc')->toString()) === 'desc' ? 'desc' : 'asc';
        $perPage = (int) $request->integer('per_page', 10);
        $search = Str::of($request->string('search')->toString())
            ->replaceMatches('/\s+/', ' ')
            ->trim();

        $allowedSorts = ['identifier', 'brand_name', 'model_name', 'status', 'created_at'];
        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'identifier';
        }

        $sortColumn = match ($sortBy) {
            'brand_name' => 'workshop_brands.name',
            'model_name' => 'workshop_models.name',
            'status' => 'workshop_equipments.status',
            'created_at' => 'workshop_equipments.created_at',
            default => 'workshop_equipments.identifier',
        };

        $allowedPerPage = [5, 10, 20, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $accentInsensitiveCollation = $this->resolveAccentInsensitiveCollation();
        $searchableColumns = ['workshop_equipments.identifier', 'workshop_equipments.note'];
        $brandTable = (new WorkshopBrand())->getTable();
        $modelTable = (new WorkshopModel())->getTable();
        $grammar = DB::query()->getGrammar();

        $tokens = $search->isEmpty()
            ? collect()
            : collect(explode(' ', Str::ascii(Str::lower($search))))
                ->filter()
                ->unique()
                ->values();

        $query = WorkshopEquipment::query()
            ->select('workshop_equipments.*')
            ->with(['brand', 'model'])
            ->leftJoin('workshop_brands', 'workshop_equipments.brand_id', '=', 'workshop_brands.id')
            ->leftJoin('workshop_models', 'workshop_equipments.model_id', '=', 'workshop_models.id')
            ->when($tokens->isNotEmpty(), function ($builder) use ($tokens, $searchableColumns, $accentInsensitiveCollation, $grammar, $brandTable, $modelTable): void {
                $builder->where(function ($outer) use ($tokens, $searchableColumns, $accentInsensitiveCollation, $grammar, $brandTable, $modelTable): void {
                    $tokens->each(function (string $token) use ($outer, $searchableColumns, $accentInsensitiveCollation, $grammar, $brandTable, $modelTable): void {
                        $outer->where(function ($inner) use ($token, $searchableColumns, $accentInsensitiveCollation, $grammar, $brandTable, $modelTable): void {
                            foreach ($searchableColumns as $column) {
                                $wrapped = $grammar->wrap($column);
                                $inner->orWhereRaw(
                                    "LOWER(CONVERT({$wrapped} USING utf8mb4) COLLATE {$accentInsensitiveCollation}) LIKE ?",
                                    ["%{$token}%"]
                                );
                            }

                            $statusMatches = collect([
                                'A' => Str::ascii(Str::lower(gettext('Activo'))),
                                'I' => Str::ascii(Str::lower(gettext('Inactivo'))),
                                'T' => Str::ascii(Str::lower(gettext('En papelera'))),
                            ])->filter(fn ($label) => str_contains($label, $token));

                            if ($statusMatches->isNotEmpty()) {
                                $inner->orWhere(function ($statusQuery) use ($statusMatches): void {
                                    foreach ($statusMatches as $statusCode => $label) {
                                        $statusQuery->orWhere('workshop_equipments.status', $statusCode);
                                    }
                                });
                            }

                            $inner->orWhereRaw(
                                "LOWER(CONVERT(" . $grammar->wrap("{$brandTable}.name") . " USING utf8mb4) COLLATE {$accentInsensitiveCollation}) LIKE ?",
                                ["%{$token}%"]
                            );

                            $inner->orWhereRaw(
                                "LOWER(CONVERT(" . $grammar->wrap("{$modelTable}.name") . " USING utf8mb4) COLLATE {$accentInsensitiveCollation}) LIKE ?",
                                ["%{$token}%"]
                            );
                        });
                    });
                });
            })
            ->orderBy($sortColumn, $sortDirection);

        $paginator = $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (WorkshopEquipment $equipment) => $this->transformEquipment($equipment));

        return response()->json([
            'status' => 'success',
            'message' => gettext('Equipos obtenidos correctamente.'),
            'data' => [
                'items' => $paginator->items(),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'last_page' => $paginator->lastPage(),
                    'total' => $paginator->total(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ],
            ],
        ]);
    }

    public function show(WorkshopEquipment $workshopEquipment): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => gettext('Equipo obtenido correctamente.'),
            'data' => [
                'item' => $this->transformEquipment($workshopEquipment->load(['brand', 'model'])),
            ],
        ]);
    }

    public function store(StoreWorkshopEquipmentRequest $request): JsonResponse
    {
        $user = Auth::user();
        $brandId = $request->input('brand_id');
        $modelId = $request->input('model_id');

        $brand = WorkshopBrand::query()
            ->where('company_id', $user?->company_id)
            ->findOrFail($brandId);

        $model = WorkshopModel::query()
            ->where('company_id', $user?->company_id)
            ->where('brand_id', $brand->id)
            ->findOrFail($modelId);

        $equipment = new WorkshopEquipment($request->validated());
        $equipment->company()->associate($user?->company_id);
        $equipment->brand()->associate($brand);
        $equipment->model()->associate($model);
        $equipment->status = $equipment->status ?? 'A';
        $equipment->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El equipo se creó correctamente.'),
            'data' => [
                'item' => $this->transformEquipment($equipment->refresh()->load(['brand', 'model'])),
            ],
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateWorkshopEquipmentRequest $request, WorkshopEquipment $workshopEquipment): JsonResponse
    {
        $workshopEquipment->fill($request->validated());

        if ($request->filled('brand_id')) {
            $brand = WorkshopBrand::query()
                ->where('company_id', $workshopEquipment->company_id)
                ->findOrFail($request->input('brand_id'));
            $workshopEquipment->brand()->associate($brand);
        }

        if ($request->filled('model_id')) {
            $model = WorkshopModel::query()
                ->where('company_id', $workshopEquipment->company_id)
                ->where('brand_id', $workshopEquipment->brand_id)
                ->findOrFail($request->input('model_id'));
            $workshopEquipment->model()->associate($model);
        }

        $workshopEquipment->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El equipo se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformEquipment($workshopEquipment->refresh()->load(['brand', 'model'])),
            ],
        ]);
    }

    public function destroy(WorkshopEquipment $workshopEquipment): JsonResponse
    {
        $deletedId = $workshopEquipment->id;
        $workshopEquipment->delete();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El equipo se eliminó correctamente.'),
            'data' => [
                'deleted_id' => $deletedId,
            ],
        ]);
    }

    public function toggleStatus(Request $request, WorkshopEquipment $workshopEquipment): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['A', 'I', 'T'])],
        ]);

        $workshopEquipment->status = $validated['status'];
        $workshopEquipment->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El estado se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformEquipment($workshopEquipment->refresh()->load(['brand', 'model'])),
            ],
        ]);
    }

    public function barcodeLabel(WorkshopEquipment $workshopEquipment): Response
    {
        $workshopEquipment->loadMissing(['brand', 'model']);

        $barcodeValue = Str::upper(trim((string) $workshopEquipment->identifier));

        if ($barcodeValue === '') {
            abort(404, gettext('El equipo no tiene identificador configurado.'));
        }

        $generator = new BarcodeGeneratorPNG();
        $barcodeImage = base64_encode(
            $generator->getBarcode($barcodeValue, BarcodeGeneratorPNG::TYPE_CODE_128)
        );

        $displayName = collect([
            $workshopEquipment->brand?->name,
            $workshopEquipment->model?->name,
        ])->filter()->implode(' · ');

        if ($displayName === '') {
            $displayName = $barcodeValue;
        }

        $html = view('Workshop.Equipments.BarcodeLabel', [
            'equipment' => $workshopEquipment,
            'barcodeValue' => $barcodeValue,
            'barcodeImage' => $barcodeImage,
            'displayName' => $displayName,
        ])->render();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->setDefaultFont('Helvetica');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper([0, 0, 175.68, 62.64]);
        $dompdf->render();

        $output = $dompdf->output();
        if (! empty($_SERVER['HTTP_ACCEPT_ENCODING'])) {
            header_remove('Transfer-Encoding');
        }

        return response($output, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="barcode-'.$barcodeValue.'.pdf"',
            'Content-Length' => strlen($output),
        ]);
    }

    private function resolveAccentInsensitiveCollation(): string
    {
        $defaultConnection = config('database.default');
        $connectionConfig = config("database.connections.{$defaultConnection}", []);

        return $connectionConfig['search_collation']
            ?? $connectionConfig['collation']
            ?? 'utf8mb4_0900_ai_ci';
    }

    private function transformEquipment(WorkshopEquipment $equipment): array
    {
        $displayName = collect([$equipment->brand?->name, $equipment->model?->name])
            ->filter()
            ->implode(' · ');

        if ($displayName === '') {
            $displayName = $equipment->identifier ?? '';
        }

        $hasBarcode = Str::upper(trim((string) $equipment->identifier)) !== '';

        return [
            'id' => $equipment->id,
            'brand_id' => $equipment->brand_id,
            'brand_name' => $equipment->brand?->name,
            'model_id' => $equipment->model_id,
            'model_name' => $equipment->model?->name,
            'identifier' => $equipment->identifier,
            'note' => $equipment->note,
            'status' => $equipment->status,
            'status_label' => $equipment->status_label,
            'display_name' => $displayName,
            'barcode_label_url' => $hasBarcode ? route('taller.equipos.barcode', $equipment) : null,
            'has_barcode' => $hasBarcode,
            'created_at' => optional($equipment->created_at)->toIso8601String(),
            'updated_at' => optional($equipment->updated_at)->toIso8601String(),
        ];
    }
}
