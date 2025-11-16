<?php

namespace App\Http\Controllers\Workshop;

use App\Contracts\SeoMetaManagerContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workshop\States\StoreWorkshopStateRequest;
use App\Http\Requests\Workshop\States\UpdateWorkshopStateRequest;
use App\Models\WorkshopCategory;
use App\Models\WorkshopState;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WorkshopStateController extends Controller
{
    public function __construct(private readonly SeoMetaManagerContract $seoMetaManager)
    {
    }

    public function index(): View
    {
        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Estados de taller',
            'description' => 'Gestiona los estados disponibles para las órdenes de trabajo del taller.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Taller')],
            ['label' => gettext('Estados')],
        ];

        return view('Workshop.States', compact('meta', 'breadcrumbItems'));
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

        $sortColumn = match ($sortBy) {
            'status' => 'workshop_states.status',
            'created_at' => 'workshop_states.created_at',
            default => 'workshop_states.name',
        };

        $allowedPerPage = [5, 10, 20, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $accentInsensitiveCollation = $this->resolveAccentInsensitiveCollation();
        $searchableColumns = ['name', 'description'];
        $categoryTable = (new WorkshopCategory())->getTable();
        $grammar = DB::query()->getGrammar();

        $tokens = $search->isEmpty()
            ? collect()
            : collect(explode(' ', Str::ascii(Str::lower($search))))
                ->filter()
                ->unique()
                ->values();

        $query = WorkshopState::query()
            ->with('category')
            ->when($tokens->isNotEmpty(), function ($builder) use ($tokens, $searchableColumns, $accentInsensitiveCollation, $grammar, $categoryTable): void {
                $builder->where(function ($outer) use ($tokens, $searchableColumns, $accentInsensitiveCollation, $grammar, $categoryTable): void {
                    $tokens->each(function (string $token) use ($outer, $searchableColumns, $accentInsensitiveCollation, $grammar, $categoryTable): void {
                        $outer->where(function ($inner) use ($token, $searchableColumns, $accentInsensitiveCollation, $grammar, $categoryTable): void {
                            foreach ($searchableColumns as $column) {
                                $wrapped = $grammar->wrap("workshop_states.{$column}");
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

                            $inner->orWhereHas('category', function ($categoryQuery) use ($token, $accentInsensitiveCollation, $grammar, $categoryTable): void {
                                $wrapped = $grammar->wrap("{$categoryTable}.name");
                                $categoryQuery->whereRaw(
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
            ->through(fn (WorkshopState $state) => $this->transformState($state));

        return response()->json([
            'status' => 'success',
            'message' => gettext('Estados obtenidos correctamente.'),
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

    public function show(WorkshopState $workshopState): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => gettext('Estado obtenido correctamente.'),
            'data' => [
                'item' => $this->transformState($workshopState),
            ],
        ]);
    }

    public function store(StoreWorkshopStateRequest $request): JsonResponse
    {
        $user = Auth::user();
        $categoryId = $request->input('category_id');
        $category = WorkshopCategory::query()
            ->where('company_id', $user?->company_id)
            ->findOrFail($categoryId);

        $state = new WorkshopState($request->validated());
        $state->company()->associate($user?->company_id);
        $state->status = $state->status ?? 'A';
        $state->category()->associate($category);
        $state->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El estado se creó correctamente.'),
            'data' => [
                'item' => $this->transformState($state->refresh()->load('category')),
            ],
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateWorkshopStateRequest $request, WorkshopState $workshopState): JsonResponse
    {
        $workshopState->fill($request->validated());

        if ($request->filled('category_id')) {
            $category = WorkshopCategory::query()
                ->where('company_id', $workshopState->company_id)
                ->findOrFail($request->input('category_id'));
            $workshopState->category()->associate($category);
        }

        $workshopState->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El estado se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformState($workshopState->refresh()->load('category')),
            ],
        ]);
    }

    public function destroy(WorkshopState $workshopState): JsonResponse
    {
        $deletedId = $workshopState->id;
        $workshopState->delete();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El estado se eliminó correctamente.'),
            'data' => [
                'deleted_id' => $deletedId,
            ],
        ]);
    }

    public function toggleStatus(Request $request, WorkshopState $workshopState): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['A', 'I', 'T'])],
        ]);

        $workshopState->status = $validated['status'];
        $workshopState->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El estado se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformState($workshopState->refresh()->load('category')),
            ],
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        $search = Str::of($request->string('search')->toString())
            ->replaceMatches('/\s+/', ' ')
            ->trim();
        $categoryFilter = $request->string('category_id')->toString();

        $accentInsensitiveCollation = $this->resolveAccentInsensitiveCollation();
        $grammar = DB::query()->getGrammar();

        $query = WorkshopState::query()
            ->where('status', 'A')
            ->when($companyId, fn ($builder) => $builder->where('company_id', $companyId))
            ->when($categoryFilter, fn ($builder) => $builder->where('category_id', $categoryFilter))
            ->orderBy('name');

        if ($search->isNotEmpty()) {
            $token = Str::ascii(Str::lower($search));
            $wrapped = $grammar->wrap('name');
            $query->whereRaw(
                "LOWER(CONVERT({$wrapped} USING utf8mb4) COLLATE {$accentInsensitiveCollation}) LIKE ?",
                ["%{$token}%"]
            );
        }

        $states = $query->get()->map(fn (WorkshopState $state) => [
            'id' => $state->id,
            'name' => $state->name,
            'category_id' => $state->category_id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => gettext('Estados obtenidos correctamente.'),
            'data' => [
                'items' => $states,
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

    private function transformState(WorkshopState $state): array
    {
        return [
            'id' => $state->id,
            'category_id' => $state->category_id,
            'category_name' => $state->category?->name,
            'name' => $state->name,
            'description' => $state->description,
            'status' => $state->status,
            'status_label' => $state->status_label,
            'created_at' => optional($state->created_at)->toIso8601String(),
            'updated_at' => optional($state->updated_at)->toIso8601String(),
        ];
    }
}


