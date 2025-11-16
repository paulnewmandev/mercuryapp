<?php

namespace App\Http\Controllers\Configuration;

use App\Contracts\SeoMetaManagerContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceCategories\StoreServiceCategoryRequest;
use App\Http\Requests\ServiceCategories\UpdateServiceCategoryRequest;
use App\Models\ServiceCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ServiceCategoryController extends Controller
{
    public function __construct(private readonly SeoMetaManagerContract $seoMetaManager)
    {
    }

    public function index(): View
    {
        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Categorías de servicios',
            'description' => 'Organiza las categorías que agrupan los servicios ofrecidos.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Servicios'), 'url' => route('configuration.services')],
            ['label' => gettext('Categorías de servicios')],
        ];

        return view('Configuration.ServiceCategories', compact('meta', 'breadcrumbItems'));
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
        $searchableColumns = ['name'];
        $grammar = DB::query()->getGrammar();

        $tokens = $search->isEmpty()
            ? collect()
            : collect(explode(' ', Str::ascii(Str::lower($search))))
                ->filter()
                ->unique()
                ->values();

        $query = ServiceCategory::query()
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
            ->with('parent')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (ServiceCategory $category) => $this->transformCategory($category));

        return response()->json([
            'status' => 'success',
            'message' => gettext('Categorías obtenidas correctamente.'),
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

    public function show(ServiceCategory $serviceCategory): JsonResponse
    {
        $serviceCategory->load('parent');

        return response()->json([
            'status' => 'success',
            'message' => gettext('Categoría obtenida correctamente.'),
            'data' => [
                'item' => $this->transformCategory($serviceCategory),
            ],
        ]);
    }

    public function store(StoreServiceCategoryRequest $request): JsonResponse
    {
        $user = Auth::user();

        $category = new ServiceCategory($request->validated());
        $category->company()->associate($user->company_id);
        $category->status = $category->status ?? 'A';

        if ($category->parent_id) {
            $parent = ServiceCategory::query()
                ->where('company_id', $user->company_id)
                ->find($category->parent_id);
            $category->parent()->associate($parent);
        }

        $category->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('La categoría se creó correctamente.'),
            'data' => [
                'item' => $this->transformCategory($category->refresh()->load('parent')),
            ],
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateServiceCategoryRequest $request, ServiceCategory $serviceCategory): JsonResponse
    {
        $serviceCategory->fill($request->validated());

        if ($serviceCategory->parent_id) {
            $parent = ServiceCategory::query()
                ->where('company_id', $serviceCategory->company_id)
                ->find($serviceCategory->parent_id);
            $serviceCategory->parent()->associate($parent);
        }

        $serviceCategory->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('La categoría se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformCategory($serviceCategory->refresh()->load('parent')),
            ],
        ]);
    }

    public function destroy(ServiceCategory $serviceCategory): JsonResponse
    {
        $deletedId = $serviceCategory->id;
        $serviceCategory->delete();

        return response()->json([
            'status' => 'success',
            'message' => gettext('La categoría se eliminó correctamente.'),
            'data' => [
                'deleted_id' => $deletedId,
            ],
        ]);
    }

    public function toggleStatus(Request $request, ServiceCategory $serviceCategory): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['A', 'I', 'T'])],
        ]);

        $serviceCategory->status = $validated['status'];
        $serviceCategory->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El estado se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformCategory($serviceCategory->refresh()->load('parent')),
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

        $query = ServiceCategory::query()
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

        $categories = $query->get()->map(fn (ServiceCategory $category) => [
            'id' => $category->id,
            'name' => $category->name,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => gettext('Categorías obtenidas correctamente.'),
            'data' => [
                'items' => $categories,
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

    private function transformCategory(ServiceCategory $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'parent_id' => $category->parent_id,
            'parent_name' => optional($category->parent)->name,
            'status' => $category->status,
            'status_label' => $category->status_label,
            'created_at' => optional($category->created_at)->toIso8601String(),
            'updated_at' => optional($category->updated_at)->toIso8601String(),
        ];
    }
}
