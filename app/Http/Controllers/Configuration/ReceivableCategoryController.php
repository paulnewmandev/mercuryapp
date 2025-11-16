<?php

namespace App\Http\Controllers\Configuration;

use App\Contracts\SeoMetaManagerContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReceivableCategories\StoreReceivableCategoryRequest;
use App\Http\Requests\ReceivableCategories\UpdateReceivableCategoryRequest;
use App\Models\ReceivableCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ReceivableCategoryController extends Controller
{
    public function __construct(private readonly SeoMetaManagerContract $seoMetaManager)
    {
    }

    public function index(): View
    {
        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Cuentas por cobrar',
            'description' => 'Administra las categorías de cuentas por cobrar.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Configuración'), 'url' => route('configuration.company')],
            ['label' => gettext('Cuentas por cobrar')],
        ];

        return view('Configuration.ReceivableCategories', compact('meta', 'breadcrumbItems'));
    }

    public function list(Request $request): JsonResponse
    {
        $sortBy = $request->string('sort_by', 'name')->toString();
        $sortDirection = Str::lower($request->string('sort_direction', 'asc')->toString()) === 'desc' ? 'desc' : 'asc';
        $perPage = (int) $request->integer('per_page', 10);
        $search = Str::of($request->string('search')->toString())
            ->replaceMatches('/\s+/', ' ')
            ->trim();

        $allowedSorts = ['code', 'name', 'status', 'created_at'];
        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'name';
        }

        $allowedPerPage = [5, 10, 20, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $accentInsensitiveCollation = $this->resolveAccentInsensitiveCollation();
        $searchableColumns = ['code', 'name', 'description'];

        $tokens = $search->isEmpty()
            ? collect()
            : collect(explode(' ', Str::ascii(Str::lower($search))))
                ->filter()
                ->unique()
                ->values();

        $query = ReceivableCategory::query()
            ->when($tokens->isNotEmpty(), function ($builder) use ($tokens, $searchableColumns, $accentInsensitiveCollation): void {
                $builder->where(function ($outer) use ($tokens, $searchableColumns, $accentInsensitiveCollation): void {
                    $grammar = DB::query()->getGrammar();

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
            ->through(fn (ReceivableCategory $category) => $this->transformCategory($category));

        return response()->json([
            'status' => 'success',
            'message' => gettext('Categorías de cuentas por cobrar obtenidas correctamente.'),
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

    public function show(ReceivableCategory $receivableCategory): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => gettext('Categoría obtenida correctamente.'),
            'data' => [
                'item' => $this->transformCategory($receivableCategory),
            ],
        ]);
    }

    public function store(StoreReceivableCategoryRequest $request): JsonResponse
    {
        $user = Auth::user();

        $category = new ReceivableCategory($request->validated());
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

    public function update(UpdateReceivableCategoryRequest $request, ReceivableCategory $receivableCategory): JsonResponse
    {
        $receivableCategory->fill($request->validated());
        $receivableCategory->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('La categoría se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformCategory($receivableCategory->refresh()),
            ],
        ]);
    }

    public function destroy(ReceivableCategory $receivableCategory): JsonResponse
    {
        $deletedId = $receivableCategory->id;
        $receivableCategory->delete();

        return response()->json([
            'status' => 'success',
            'message' => gettext('La categoría se eliminó correctamente.'),
            'data' => [
                'deleted_id' => $deletedId,
            ],
        ]);
    }

    public function toggleStatus(Request $request, ReceivableCategory $receivableCategory): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['A', 'I', 'T'])],
        ]);

        $receivableCategory->status = $validated['status'];
        $receivableCategory->save();

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
                'item' => $this->transformCategory($receivableCategory->refresh()),
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

    private function transformCategory(ReceivableCategory $category): array
    {
        return [
            'id' => $category->id,
            'code' => $category->code,
            'name' => $category->name,
            'description' => $category->description,
            'status' => $category->status,
            'status_label' => $category->status_label,
            'created_at' => optional($category->created_at)->toIso8601String(),
            'updated_at' => optional($category->updated_at)->toIso8601String(),
        ];
    }
}

