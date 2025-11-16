<?php

namespace App\Http\Controllers\Workshop;

use App\Contracts\SeoMetaManagerContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workshop\Brands\StoreWorkshopBrandRequest;
use App\Http\Requests\Workshop\Brands\UpdateWorkshopBrandRequest;
use App\Models\WorkshopBrand;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WorkshopBrandController extends Controller
{
    public function __construct(private readonly SeoMetaManagerContract $seoMetaManager)
    {
    }

    public function index(): View
    {
        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Marcas de taller',
            'description' => 'Administra las marcas disponibles para clasificar los productos del taller.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Taller')],
            ['label' => gettext('Marcas')],
        ];

        return view('Workshop.Brands', compact('meta', 'breadcrumbItems'));
    }

    public function list(Request $request): JsonResponse
    {
        $sortBy = $request->string('sort_by', 'name')->toString();
        $sortDirection = Str::lower($request->string('sort_direction', 'asc')->toString()) === 'desc' ? 'desc' : 'asc';
        $perPage = (int) $request->integer('per_page', 10);
        $search = Str::of($request->string('search')->toString())
            ->replaceMatches('/\s+/', ' ')
            ->trim();

        $allowedSorts = ['name', 'status', 'created_at'];
        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'name';
        }

        $allowedPerPage = [5, 10, 20, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $accentInsensitiveCollation = $this->resolveAccentInsensitiveCollation();
        $searchableColumns = ['name', 'description'];
        $grammar = DB::query()->getGrammar();

        $tokens = $search->isEmpty()
            ? collect()
            : collect(explode(' ', Str::ascii(Str::lower($search))))
                ->filter()
                ->unique()
                ->values();

        $query = WorkshopBrand::query()
            ->when($tokens->isNotEmpty(), function ($builder) use ($tokens, $searchableColumns, $accentInsensitiveCollation, $grammar): void {
                $builder->where(function ($outer) use ($tokens, $searchableColumns, $accentInsensitiveCollation, $grammar): void {
                    $tokens->each(function (string $token) use ($outer, $searchableColumns, $accentInsensitiveCollation, $grammar): void {
                        $outer->where(function ($inner) use ($token, $searchableColumns, $accentInsensitiveCollation, $grammar): void {
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
                        });
                    });
                });
            })
            ->orderBy($sortBy, $sortDirection);

        $paginator = $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (WorkshopBrand $brand) => $this->transformBrand($brand));

        return response()->json([
            'status' => 'success',
            'message' => gettext('Marcas obtenidas correctamente.'),
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

    public function show(WorkshopBrand $workshopBrand): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => gettext('Marca obtenida correctamente.'),
            'data' => [
                'item' => $this->transformBrand($workshopBrand),
            ],
        ]);
    }

    public function store(StoreWorkshopBrandRequest $request): JsonResponse
    {
        $user = Auth::user();

        $brand = new WorkshopBrand($request->validated());
        $brand->company()->associate($user?->company_id);
        $brand->status = $brand->status ?? 'A';
        $brand->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('La marca se creó correctamente.'),
            'data' => [
                'item' => $this->transformBrand($brand->refresh()),
            ],
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateWorkshopBrandRequest $request, WorkshopBrand $workshopBrand): JsonResponse
    {
        $workshopBrand->fill($request->validated());
        $workshopBrand->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('La marca se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformBrand($workshopBrand->refresh()),
            ],
        ]);
    }

    public function destroy(WorkshopBrand $workshopBrand): JsonResponse
    {
        $deletedId = $workshopBrand->id;
        $workshopBrand->delete();

        return response()->json([
            'status' => 'success',
            'message' => gettext('La marca se eliminó correctamente.'),
            'data' => [
                'deleted_id' => $deletedId,
            ],
        ]);
    }

    public function toggleStatus(Request $request, WorkshopBrand $workshopBrand): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['A', 'I', 'T'])],
        ]);

        $workshopBrand->status = $validated['status'];
        $workshopBrand->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El estado se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformBrand($workshopBrand->refresh()),
            ],
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        $search = Str::of($request->string('search')->toString())
            ->replaceMatches('/\s+/', ' ')
            ->trim();

        $accentInsensitiveCollation = $this->resolveAccentInsensitiveCollation();
        $grammar = DB::query()->getGrammar();

        $query = WorkshopBrand::query()
            ->where('status', 'A')
            ->orderBy('name');

        if ($search->isNotEmpty()) {
            $token = Str::ascii(Str::lower($search));
            $wrapped = $grammar->wrap('name');
            $query->whereRaw(
                "LOWER(CONVERT({$wrapped} USING utf8mb4) COLLATE {$accentInsensitiveCollation}) LIKE ?",
                ["%{$token}%"]
            );
        }

        $brands = $query->get()->map(fn (WorkshopBrand $brand) => [
            'id' => $brand->id,
            'name' => $brand->name,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => gettext('Marcas obtenidas correctamente.'),
            'data' => [
                'items' => $brands,
            ],
        ]);
    }

    private function resolveAccentInsensitiveCollation(): string
    {
        $defaultConnection = config('database.default');
        $connectionConfig = config("database.connections.{$defaultConnection}", []);

        return $connectionConfig['search_collation']
            ?? $connectionConfig['collation']
            ?? 'utf8mb4_unicode_ci';
    }

    private function transformBrand(WorkshopBrand $brand): array
    {
        return [
            'id' => $brand->id,
            'name' => $brand->name,
            'description' => $brand->description,
            'status' => $brand->status,
            'status_label' => $brand->status_label,
            'created_at' => optional($brand->created_at)->toIso8601String(),
            'updated_at' => optional($brand->updated_at)->toIso8601String(),
        ];
    }
}
