<?php

namespace App\Http\Controllers\Configuration;

use App\Contracts\SeoMetaManagerContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductCategories\StoreProductCategoryRequest;
use App\Http\Requests\ProductCategories\UpdateProductCategoryRequest;
use App\Models\ProductCategory;
use App\Models\ProductLine;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductCategoryController extends Controller
{
    public function __construct(private readonly SeoMetaManagerContract $seoMetaManager)
    {
    }

    public function index(): View
    {
        $lines = ProductLine::query()->orderBy('name')->get(['id', 'name']);
        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Categorías de producto',
            'description' => 'Gestiona las categorías y subcategorías de tus productos Apple.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Catálogo'), 'url' => route('catalog.categories')],
            ['label' => gettext('Categorías')],
        ];

        return view('Configuration.ProductCategories', compact('meta', 'breadcrumbItems', 'lines'));
    }

    public function subcategories(): View
    {
        $lines = ProductLine::query()->orderBy('name')->get(['id', 'name']);
        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Subcategorías de producto',
            'description' => 'Define subcategorías detalladas para clasificar reparaciones y repuestos Apple.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Catálogo'), 'url' => route('catalog.subcategories')],
            ['label' => gettext('Subcategorías')],
        ];

        return view('Configuration.ProductSubcategories', compact('meta', 'breadcrumbItems', 'lines'));
    }

    public function list(Request $request): JsonResponse
    {
        $sortBy = $request->string('sort_by', 'name')->toString();
        $sortDirection = Str::lower($request->string('sort_direction', 'asc')->toString()) === 'desc' ? 'desc' : 'asc';
        $perPage = (int) $request->integer('per_page', 10);
        $search = Str::of($request->string('search')->toString())
            ->replaceMatches('/\s+/', ' ')
            ->trim();

        $parentScope = $request->string('parent_scope')->toString();
        $parentId = $request->string('parent_id')->toString();
        $productLineFilter = $request->string('product_line_id')->toString();

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

        $query = ProductCategory::query()
            ->when($parentScope === 'root', fn ($builder) => $builder->whereNull('parent_id'))
            ->when($parentScope === 'children', fn ($builder) => $builder->whereNotNull('parent_id'))
            ->when($parentId !== '', fn ($builder) => $builder->where('parent_id', $parentId))
            ->when($productLineFilter !== '', fn ($builder) => $builder->where('product_line_id', $productLineFilter))
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
            ->with(['parent', 'line'])
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (ProductCategory $category) => $this->transformProductCategory($category));

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

    public function show(ProductCategory $productCategory): JsonResponse
    {
        $productCategory->load(['parent', 'line']);

        return response()->json([
            'status' => 'success',
            'message' => gettext('Categoría obtenida correctamente.'),
            'data' => [
                'item' => $this->transformProductCategory($productCategory),
            ],
        ]);
    }

    public function store(StoreProductCategoryRequest $request): JsonResponse
    {
        $user = Auth::user();

        $category = new ProductCategory($request->validated());
        $category->company()->associate($user->company_id);
        $category->status = $category->status ?? 'A';
        if ($category->parent_id) {
            $parent = ProductCategory::query()
                ->where('company_id', $user->company_id)
                ->find($category->parent_id);
            $category->product_line_id = $parent?->product_line_id;
        }
        $category->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('La categoría se creó correctamente.'),
            'data' => [
                'item' => $this->transformProductCategory($category->refresh()->load(['parent', 'line'])),
            ],
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateProductCategoryRequest $request, ProductCategory $productCategory): JsonResponse
    {
        $productCategory->fill($request->validated());
        if ($productCategory->parent_id) {
            $parent = ProductCategory::query()
                ->where('company_id', $productCategory->company_id)
                ->find($productCategory->parent_id);
            $productCategory->product_line_id = $parent?->product_line_id;
        }
        $productCategory->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('La categoría se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformProductCategory($productCategory->refresh()->load(['parent', 'line'])),
            ],
        ]);
    }

    public function destroy(ProductCategory $productCategory): JsonResponse
    {
        $deletedId = $productCategory->id;
        $productCategory->delete();

        return response()->json([
            'status' => 'success',
            'message' => gettext('La categoría se eliminó correctamente.'),
            'data' => [
                'deleted_id' => $deletedId,
            ],
        ]);
    }

    public function toggleStatus(Request $request, ProductCategory $productCategory): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['A', 'I', 'T'])],
        ]);

        $productCategory->status = $validated['status'];
        $productCategory->save();

        $message = match ($validated['status']) {
            'A' => gettext('La categoría se activó correctamente.'),
            'I' => gettext('La categoría se desactivó correctamente.'),
            'T' => gettext('La categoría se movió a la papelera.'),
            default => gettext('Estado actualizado correctamente.'),
        };

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => [
                'item' => $this->transformProductCategory($productCategory->refresh()->load(['parent', 'line'])),
            ],
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        $scope = $request->string('parent_scope')->toString();
        $parentId = $request->string('parent_id')->toString();
        $productLineId = $request->string('product_line_id')->toString();

        $query = ProductCategory::query()
            ->when($scope === 'root', fn ($builder) => $builder->whereNull('parent_id'))
            ->when($scope === 'children', fn ($builder) => $builder->whereNotNull('parent_id'))
            ->when($parentId !== '', fn ($builder) => $builder->where('parent_id', $parentId))
            ->where('status', 'A')
            ->orderBy('name');

        if ($productLineId !== '') {
            $query->where('product_line_id', $productLineId);
        }

        $options = $query->get()->map(fn (ProductCategory $category) => [
            'id' => $category->id,
            'name' => $category->name,
            'parent_id' => $category->parent_id,
            'product_line_id' => $category->product_line_id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => gettext('Opciones obtenidas correctamente.'),
            'data' => [
                'items' => $options,
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

    private function transformProductCategory(ProductCategory $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'product_line_id' => $category->product_line_id,
            'product_line_name' => optional($category->line)->name,
            'parent_id' => $category->parent_id,
            'parent_name' => optional($category->parent)->name,
            'status' => $category->status,
            'status_label' => $category->status_label,
            'created_at' => optional($category->created_at)->toIso8601String(),
            'updated_at' => optional($category->updated_at)->toIso8601String(),
        ];
    }
}
