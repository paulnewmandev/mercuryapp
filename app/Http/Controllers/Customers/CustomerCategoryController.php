<?php

namespace App\Http\Controllers\Customers;

use App\Contracts\SeoMetaManagerContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customers\StoreCustomerCategoryRequest;
use App\Http\Requests\Customers\UpdateCustomerCategoryRequest;
use App\Models\CustomerCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CustomerCategoryController extends Controller
{
    public function __construct(private readonly SeoMetaManagerContract $seoMetaManager)
    {
    }

    public function index(): View
    {
        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Categorías de clientes',
            'description' => 'Administra las categorías de clientes para segmentar tu base.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Clientes'), 'url' => route('clientes.naturales')],
            ['label' => gettext('Categorías')],
        ];

        return view('CRM.CustomerCategories', compact('meta', 'breadcrumbItems'));
    }

    public function list(Request $request): JsonResponse
    {
        $sortBy = $request->string('sort_by', 'created_at')->toString();
        $sortDirection = Str::lower($request->string('sort_direction', 'desc')->toString()) === 'desc' ? 'desc' : 'asc';
        $perPage = (int) $request->integer('per_page', 10);
        $search = Str::of($request->string('search')->toString())
            ->replaceMatches('/\s+/', ' ')
            ->trim();

        $allowedSorts = ['name', 'status', 'created_at'];
        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
            $sortDirection = 'desc';
        }

        $allowedPerPage = [5, 10, 20, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $accentInsensitiveCollation = $this->resolveAccentInsensitiveCollation();
        $searchableColumns = ['customer_categories.name', 'customer_categories.description'];
        $grammar = DB::query()->getGrammar();

        $tokens = $search->isEmpty()
            ? collect()
            : collect(explode(' ', Str::ascii(Str::lower($search))))
                ->filter()
                ->unique()
                ->values();

        $query = CustomerCategory::query()
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
                                        $statusQuery->orWhere('customer_categories.status', $statusCode);
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
            ->through(fn (CustomerCategory $category) => $this->transformCategory($category));

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

    public function show(CustomerCategory $category): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => gettext('Categoría obtenida correctamente.'),
            'data' => [
                'item' => $this->transformCategory($category),
            ],
        ]);
    }

    public function store(StoreCustomerCategoryRequest $request): JsonResponse
    {
        $user = Auth::user();

        $category = new CustomerCategory($request->validated());
        $category->company()->associate($user->company_id);
        $category->status = $category->status ?? 'A';
        $category->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('La categoría se creó correctamente.'),
            'data' => [
                'item' => $this->transformCategory($category->refresh()),
            ],
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateCustomerCategoryRequest $request, CustomerCategory $category): JsonResponse
    {
        $category->fill($request->validated());
        $category->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('La categoría se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformCategory($category->refresh()),
            ],
        ]);
    }

    public function destroy(CustomerCategory $category): JsonResponse
    {
        $deletedId = $category->id;
        $category->delete();

        return response()->json([
            'status' => 'success',
            'message' => gettext('La categoría se eliminó correctamente.'),
            'data' => [
                'deleted_id' => $deletedId,
            ],
        ]);
    }

    public function toggleStatus(Request $request, CustomerCategory $category): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['A', 'I', 'T'])],
        ]);

        $category->status = $validated['status'];
        $category->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El estado se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformCategory($category->refresh()),
            ],
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        $search = Str::of($request->string('search')->toString())
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->lower();

        $categories = CustomerCategory::query()
            ->where('company_id', Auth::user()->company_id)
            ->where('status', 'A')
            ->when($search->isNotEmpty(), fn ($builder) => $builder->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]))
            ->orderBy('name')
            ->limit((int) $request->integer('limit', 100))
            ->get()
            ->map(fn (CustomerCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
            ]);

        return response()->json([
            'status' => 'success',
            'message' => gettext('Categorías disponibles.'),
            'data' => [
                'items' => $categories,
            ],
        ]);
    }

    private function transformCategory(CustomerCategory $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'status' => $category->status,
            'status_label' => $category->status_label,
            'created_at' => optional($category->created_at)->toIso8601String(),
            'updated_at' => optional($category->updated_at)->toIso8601String(),
        ];
    }

    private function resolveAccentInsensitiveCollation(): string
    {
        $defaultConnection = config('database.default');
        $connectionConfig = config("database.connections.{$defaultConnection}", []);

        return $connectionConfig['search_collation']
            ?? $connectionConfig['collation']
            ?? 'utf8mb4_0900_ai_ci';
    }
}

