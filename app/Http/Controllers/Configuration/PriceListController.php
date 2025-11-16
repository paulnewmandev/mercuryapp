<?php

namespace App\Http\Controllers\Configuration;

use App\Contracts\SeoMetaManagerContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\PriceLists\StorePriceListRequest;
use App\Http\Requests\PriceLists\UpdatePriceListRequest;
use App\Models\PriceList;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PriceListController extends Controller
{
    public function __construct(private readonly SeoMetaManagerContract $seoMetaManager)
    {
    }

    public function index(): View
    {
        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Listas de precios',
            'description' => 'Gestiona listas de precios para tus productos y servicios.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Inventario'), 'url' => route('configuration.price_lists')],
            ['label' => gettext('Listas de precios')],
        ];

        return view('Configuration.PriceLists', compact('meta', 'breadcrumbItems'));
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

        $query = PriceList::query()
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
            ->through(fn (PriceList $priceList) => $this->transformPriceList($priceList));

        return response()->json([
            'status' => 'success',
            'message' => gettext('Listas de precios obtenidas correctamente.'),
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

    public function show(PriceList $priceList): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => gettext('Lista de precios obtenida correctamente.'),
            'data' => [
                'item' => $this->transformPriceList($priceList),
            ],
        ]);
    }

    public function store(StorePriceListRequest $request): JsonResponse
    {
        $user = Auth::user();

        $priceList = new PriceList($request->validated());
        $priceList->company()->associate($user->company_id);
        $priceList->status = $priceList->status ?? 'A';
        $priceList->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('La lista de precios se creó correctamente.'),
            'data' => [
                'item' => $this->transformPriceList($priceList->refresh()),
            ],
        ], Response::HTTP_CREATED);
    }

    public function update(UpdatePriceListRequest $request, PriceList $priceList): JsonResponse
    {
        $priceList->fill($request->validated());
        $priceList->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('La lista de precios se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformPriceList($priceList->refresh()),
            ],
        ]);
    }

    public function destroy(PriceList $priceList): JsonResponse
    {
        $deletedId = $priceList->id;
        $priceList->delete();

        return response()->json([
            'status' => 'success',
            'message' => gettext('La lista de precios se eliminó correctamente.'),
            'data' => [
                'deleted_id' => $deletedId,
            ],
        ]);
    }

    public function toggleStatus(Request $request, PriceList $priceList): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['A', 'I', 'T'])],
        ]);

        $priceList->status = $validated['status'];
        $priceList->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El estado se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformPriceList($priceList->refresh()),
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

    public function options(): JsonResponse
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        if (!$companyId) {
            $company = \App\Models\Company::query()->first();
            $companyId = $company?->id;
        }

        $priceLists = PriceList::query()
            ->where(function ($query) use ($companyId): void {
                $query->whereNull('company_id')
                    ->orWhere('company_id', $companyId);
            })
            ->where('status', 'A')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (PriceList $priceList) => [
                'id' => $priceList->id,
                'name' => $priceList->name,
            ]);

        return response()->json([
            'status' => 'success',
            'message' => gettext('Listas de precios obtenidas correctamente.'),
            'data' => [
                'items' => $priceLists,
            ],
        ]);
    }

    private function transformPriceList(PriceList $priceList): array
    {
        return [
            'id' => $priceList->id,
            'name' => $priceList->name,
            'description' => $priceList->description,
            'status' => $priceList->status,
            'status_label' => $priceList->status_label,
            'created_at' => optional($priceList->created_at)->toIso8601String(),
            'updated_at' => optional($priceList->updated_at)->toIso8601String(),
        ];
    }
}
