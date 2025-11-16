<?php

namespace App\Http\Controllers\Configuration;

use App\Contracts\SeoMetaManagerContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\Services\StoreServiceRequest;
use App\Http\Requests\Services\UpdateServiceRequest;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    public function __construct(private readonly SeoMetaManagerContract $seoMetaManager)
    {
    }

    public function index(): View
    {
        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Servicios',
            'description' => 'Administra los servicios ofrecidos y su información principal.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Categorías de servicios'), 'url' => route('configuration.service_categories')],
            ['label' => gettext('Servicios')],
        ];

        $categories = ServiceCategory::query()->where('status', 'A')->orderBy('name')->get(['id', 'name']);

        return view('Configuration.Services', compact('meta', 'breadcrumbItems', 'categories'));
    }

    public function list(Request $request): JsonResponse
    {
        $sortBy = $request->string('sort_by', 'name')->toString();
        $sortDirection = Str::lower($request->string('sort_direction', 'asc')->toString()) === 'desc' ? 'desc' : 'asc';
        $perPage = (int) $request->integer('per_page', 10);
        $search = Str::of($request->string('search')->toString())
            ->replaceMatches('/\s+/', ' ')
            ->trim();
        $categoryFilter = $request->string('category_id')->toString();

        $allowedSorts = ['name', 'status', 'created_at'];
        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'name';
        }

        $allowedPerPage = [5, 10, 20, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $accentInsensitiveCollation = $this->resolveAccentInsensitiveCollation();
        $searchableColumns = ['services.name', 'services.description'];
        $grammar = DB::query()->getGrammar();

        $tokens = $search->isEmpty()
            ? collect()
            : collect(explode(' ', Str::ascii(Str::lower($search))))
                ->filter()
                ->unique()
                ->values();

        $query = Service::query()
            ->with('category')
            ->when($categoryFilter !== '', fn ($builder) => $builder->where('category_id', $categoryFilter))
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

                            $inner->orWhereHas('category', function ($categoryQuery) use ($token, $accentInsensitiveCollation, $grammar): void {
                                $wrappedCategory = $grammar->wrap('name');
                                $categoryQuery->whereRaw(
                                    "LOWER(CONVERT({$wrappedCategory} USING utf8mb4) COLLATE {$accentInsensitiveCollation}) LIKE ?",
                                    ["%{$token}%"]
                                );
                            });

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
            ->through(fn (Service $service) => $this->transformService($service));

        return response()->json([
            'status' => 'success',
            'message' => gettext('Servicios obtenidos correctamente.'),
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

    public function show(Service $service): JsonResponse
    {
        $service->load('category');

        return response()->json([
            'status' => 'success',
            'message' => gettext('Servicio obtenido correctamente.'),
            'data' => [
                'item' => $this->transformService($service),
            ],
        ]);
    }

    public function store(StoreServiceRequest $request): JsonResponse
    {
        $user = Auth::user();

        $service = new Service($request->validated());
        $service->company()->associate($user->company_id);
        $service->status = $service->status ?? 'A';

        if ($service->category_id) {
            $category = ServiceCategory::query()
                ->where('company_id', $user->company_id)
                ->find($service->category_id);
            $service->category()->associate($category);
        }

        $service->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El servicio se creó correctamente.'),
            'data' => [
                'item' => $this->transformService($service->refresh()->load('category')),
            ],
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateServiceRequest $request, Service $service): JsonResponse
    {
        $service->fill($request->validated());

        if ($service->category_id) {
            $category = ServiceCategory::query()
                ->where('company_id', $service->company_id)
                ->find($service->category_id);
            $service->category()->associate($category);
        }

        $service->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El servicio se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformService($service->refresh()->load('category')),
            ],
        ]);
    }

    public function destroy(Service $service): JsonResponse
    {
        $deletedId = $service->id;
        $service->delete();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El servicio se eliminó correctamente.'),
            'data' => [
                'deleted_id' => $deletedId,
            ],
        ]);
    }

    public function toggleStatus(Request $request, Service $service): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['A', 'I', 'T'])],
        ]);

        $service->status = $validated['status'];
        $service->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El estado se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformService($service->refresh()->load('category')),
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

    private function transformService(Service $service): array
    {
        $currency = $service->currency ?? 'USD';
        $price = (float) ($service->price ?? 0);
        
        return [
            'id' => $service->id,
            'category_id' => $service->category_id,
            'category_name' => optional($service->category)->name,
            'price' => $price,
            'currency' => $currency,
            'price_formatted' => number_format($price, 2, '.', ','),
            'name' => $service->name,
            'description' => $service->description,
            'status' => $service->status,
            'status_label' => $service->status_label,
            'created_at' => optional($service->created_at)->toIso8601String(),
            'updated_at' => optional($service->updated_at)->toIso8601String(),
        ];
    }
}
