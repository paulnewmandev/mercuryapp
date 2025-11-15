<?php

namespace App\Http\Controllers\Configuration;

use App\Contracts\SeoMetaManagerContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductLines\StoreProductLineRequest;
use App\Http\Requests\ProductLines\UpdateProductLineRequest;
use App\Models\ProductLine;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductLineController extends Controller
{
    public function __construct(private readonly SeoMetaManagerContract $seoMetaManager)
    {
    }

    public function index(): View
    {
        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Líneas de producto',
            'description' => 'Administra las líneas de productos para tus reparaciones y ventas.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Catálogo'), 'url' => route('catalog.lines')],
            ['label' => gettext('Líneas')],
        ];

        return view('Configuration.ProductLines', compact('meta', 'breadcrumbItems'));
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

        $tokens = $search->isEmpty()
            ? collect()
            : collect(explode(' ', Str::ascii(Str::lower($search))))
                ->filter()
                ->unique()
                ->values();

        $query = ProductLine::query()
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
            ->through(fn (ProductLine $line) => $this->transformProductLine($line));

        return response()->json([
            'status' => 'success',
            'message' => gettext('Líneas de producto obtenidas correctamente.'),
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

    public function show(ProductLine $productLine): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => gettext('Línea obtenida correctamente.'),
            'data' => [
                'item' => $this->transformProductLine($productLine),
            ],
        ]);
    }

    public function store(StoreProductLineRequest $request): JsonResponse
    {
        $user = Auth::user();

        $line = new ProductLine($request->validated());
        $line->company()->associate($user->company_id);
        $line->status = $line->status ?? 'A';
        $line->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('La línea de producto se creó correctamente.'),
            'data' => [
                'item' => $this->transformProductLine($line->refresh()),
            ],
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateProductLineRequest $request, ProductLine $productLine): JsonResponse
    {
        $productLine->fill($request->validated());
        $productLine->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('La línea de producto se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformProductLine($productLine->refresh()),
            ],
        ]);
    }

    public function destroy(ProductLine $productLine): JsonResponse
    {
        $deletedId = $productLine->id;
        $productLine->delete();

        return response()->json([
            'status' => 'success',
            'message' => gettext('La línea de producto se eliminó correctamente.'),
            'data' => [
                'deleted_id' => $deletedId,
            ],
        ]);
    }

    public function toggleStatus(Request $request, ProductLine $productLine): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['A', 'I', 'T'])],
        ]);

        $productLine->status = $validated['status'];
        $productLine->save();

        $message = match ($validated['status']) {
            'A' => gettext('La línea se activó correctamente.'),
            'I' => gettext('La línea se desactivó correctamente.'),
            'T' => gettext('La línea se movió a la papelera.'),
            default => gettext('Estado actualizado correctamente.'),
        };

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => [
                'item' => $this->transformProductLine($productLine->refresh()),
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

    private function transformProductLine(ProductLine $line): array
    {
        return [
            'id' => $line->id,
            'name' => $line->name,
            'description' => $line->description,
            'status' => $line->status,
            'status_label' => $line->status_label,
            'created_at' => optional($line->created_at)->toIso8601String(),
            'updated_at' => optional($line->updated_at)->toIso8601String(),
        ];
    }
}
