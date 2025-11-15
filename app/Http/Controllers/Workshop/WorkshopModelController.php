<?php

namespace App\Http\Controllers\Workshop;

use App\Contracts\SeoMetaManagerContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workshop\Models\StoreWorkshopModelRequest;
use App\Http\Requests\Workshop\Models\UpdateWorkshopModelRequest;
use App\Models\WorkshopBrand;
use App\Models\WorkshopModel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WorkshopModelController extends Controller
{
    public function __construct(private readonly SeoMetaManagerContract $seoMetaManager)
    {
    }

    public function index(): View
    {
        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Modelos de taller',
            'description' => 'Gestiona los modelos asociados a cada marca para clasificar los trabajos del taller.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Taller')],
            ['label' => gettext('Modelos')],
        ];

        return view('Workshop.Models', [
            'meta' => $meta,
            'breadcrumbItems' => $breadcrumbItems,
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $sortBy = $request->string('sort_by', 'name')->toString();
        $sortDirection = Str::lower($request->string('sort_direction', 'asc')->toString()) === 'desc' ? 'desc' : 'asc';
        $perPage = (int) $request->integer('per_page', 10);
        $search = Str::of($request->string('search')->toString())
            ->replaceMatches('/\s+/', ' ')
            ->trim();

        $allowedSorts = ['name', 'status', 'created_at', 'brand_name'];
        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'name';
        }

        $sortColumn = match ($sortBy) {
            'status' => 'workshop_models.status',
            'created_at' => 'workshop_models.created_at',
            'brand_name' => 'workshop_brands.name',
            default => 'workshop_models.name',
        };

        $allowedPerPage = [5, 10, 20, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $accentInsensitiveCollation = $this->resolveAccentInsensitiveCollation();
        $searchableColumns = ['workshop_models.name', 'workshop_models.description'];
        $brandTable = (new WorkshopBrand())->getTable();
        $grammar = DB::query()->getGrammar();

        $tokens = $search->isEmpty()
            ? collect()
            : collect(explode(' ', Str::ascii(Str::lower($search))))
                ->filter()
                ->unique()
                ->values();

        $query = WorkshopModel::query()
            ->select('workshop_models.*')
            ->with('brand')
            ->when($sortColumn === 'workshop_brands.name', function ($builder): void {
                $builder->leftJoin('workshop_brands', 'workshop_models.brand_id', '=', 'workshop_brands.id');
            })
            ->when($tokens->isNotEmpty(), function ($builder) use ($tokens, $searchableColumns, $accentInsensitiveCollation, $grammar, $brandTable): void {
                $builder->where(function ($outer) use ($tokens, $searchableColumns, $accentInsensitiveCollation, $grammar, $brandTable): void {
                    $tokens->each(function (string $token) use ($outer, $searchableColumns, $accentInsensitiveCollation, $grammar, $brandTable): void {
                        $outer->where(function ($inner) use ($token, $searchableColumns, $accentInsensitiveCollation, $grammar, $brandTable): void {
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
                                        $statusQuery->orWhere('status', $statusCode);
                                    }
                                });
                            }

                            $inner->orWhereHas('brand', function ($brandQuery) use ($token, $accentInsensitiveCollation, $grammar, $brandTable): void {
                                $wrapped = $grammar->wrap("{$brandTable}.name");
                                $brandQuery->whereRaw(
                                    "LOWER(CONVERT({$wrapped} USING utf8mb4) COLLATE {$accentInsensitiveCollation}) LIKE ?",
                                    ["%{$token}%"]
                                );
                            });
                        });
                    });
                });
            })
            ->orderBy($sortColumn, $sortDirection);

        $paginator = $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (WorkshopModel $model) => $this->transformModel($model));

        return response()->json([
            'status' => 'success',
            'message' => gettext('Modelos obtenidos correctamente.'),
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

    public function show(WorkshopModel $workshopModel): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => gettext('Modelo obtenido correctamente.'),
            'data' => [
                'item' => $this->transformModel($workshopModel->loadMissing('brand')),
            ],
        ]);
    }

    public function store(StoreWorkshopModelRequest $request): JsonResponse
    {
        $user = Auth::user();
        $brandId = $request->input('brand_id');
        $brand = WorkshopBrand::query()
            ->where('company_id', $user?->company_id)
            ->findOrFail($brandId);

        $model = new WorkshopModel($request->validated());
        $model->company()->associate($user?->company_id);
        $model->brand()->associate($brand);
        $model->status = $model->status ?? 'A';
        $model->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El modelo se creó correctamente.'),
            'data' => [
                'item' => $this->transformModel($model->refresh()->load('brand')),
            ],
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateWorkshopModelRequest $request, WorkshopModel $workshopModel): JsonResponse
    {
        $workshopModel->fill($request->validated());

        if ($request->filled('brand_id')) {
            $brand = WorkshopBrand::query()
                ->where('company_id', $workshopModel->company_id)
                ->findOrFail($request->input('brand_id'));
            $workshopModel->brand()->associate($brand);
        }

        $workshopModel->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El modelo se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformModel($workshopModel->refresh()->load('brand')),
            ],
        ]);
    }

    public function destroy(WorkshopModel $workshopModel): JsonResponse
    {
        $deletedId = $workshopModel->id;
        $workshopModel->delete();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El modelo se eliminó correctamente.'),
            'data' => [
                'deleted_id' => $deletedId,
            ],
        ]);
    }

    public function toggleStatus(Request $request, WorkshopModel $workshopModel): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['A', 'I', 'T'])],
        ]);

        $workshopModel->status = $validated['status'];
        $workshopModel->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El estado se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformModel($workshopModel->refresh()->load('brand')),
            ],
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        $search = Str::of($request->string('search')->toString())
            ->replaceMatches('/\s+/', ' ')
            ->trim();
        $brandFilter = $request->string('brand_id')->toString();

        $accentInsensitiveCollation = $this->resolveAccentInsensitiveCollation();
        $grammar = DB::query()->getGrammar();

        $query = WorkshopModel::query()
            ->where('status', 'A')
            ->when($brandFilter, fn ($builder, $brandId) => $builder->where('brand_id', $brandId))
            ->orderBy('name');

        if ($search->isNotEmpty()) {
            $token = Str::ascii(Str::lower($search));
            $wrapped = $grammar->wrap('name');
            $query->whereRaw(
                "LOWER(CONVERT({$wrapped} USING utf8mb4) COLLATE {$accentInsensitiveCollation}) LIKE ?",
                ["%{$token}%"]
            );
        }

        $models = $query->get()->map(fn (WorkshopModel $model) => [
            'id' => $model->id,
            'name' => $model->name,
            'brand_id' => $model->brand_id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => gettext('Modelos obtenidos correctamente.'),
            'data' => [
                'items' => $models,
            ],
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

    private function transformModel(WorkshopModel $model): array
    {
        return [
            'id' => $model->id,
            'brand_id' => $model->brand_id,
            'brand_name' => $model->brand?->name,
            'name' => $model->name,
            'description' => $model->description,
            'status' => $model->status,
            'status_label' => $model->status_label,
            'created_at' => optional($model->created_at)->toIso8601String(),
            'updated_at' => optional($model->updated_at)->toIso8601String(),
        ];
    }
}
