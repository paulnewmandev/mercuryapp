<?php

namespace App\Http\Controllers\Configuration;

use App\Contracts\SeoMetaManagerContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\IncomeTypes\StoreIncomeTypeRequest;
use App\Http\Requests\IncomeTypes\UpdateIncomeTypeRequest;
use App\Models\IncomeType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class IncomeTypeController extends Controller
{
    public function __construct(private readonly SeoMetaManagerContract $seoMetaManager)
    {
    }

    public function index(): View
    {
        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Tipos de ingresos',
            'description' => 'Administra las categorías de ingresos de tu empresa.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Configuración'), 'url' => route('configuration.company')],
            ['label' => gettext('Tipos de ingresos')],
        ];

        return view('Configuration.IncomeTypes', compact('meta', 'breadcrumbItems'));
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

        $query = IncomeType::query()
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
            ->through(fn (IncomeType $type) => $this->transformIncomeType($type));

        return response()->json([
            'status' => 'success',
            'message' => gettext('Tipos de ingresos obtenidos correctamente.'),
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

    public function show(IncomeType $incomeType): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => gettext('Tipo de ingreso obtenido correctamente.'),
            'data' => [
                'item' => $this->transformIncomeType($incomeType),
            ],
        ]);
    }

    public function store(StoreIncomeTypeRequest $request): JsonResponse
    {
        $user = Auth::user();

        $incomeType = new IncomeType($request->validated());
        $incomeType->company()->associate($user->company_id);
        $incomeType->status = $incomeType->status ?? 'A';
        $incomeType->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El tipo de ingreso se creó correctamente.'),
            'data' => [
                'item' => $this->transformIncomeType($incomeType->refresh()),
            ],
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateIncomeTypeRequest $request, IncomeType $incomeType): JsonResponse
    {
        $incomeType->fill($request->validated());
        $incomeType->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El tipo de ingreso se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformIncomeType($incomeType->refresh()),
            ],
        ]);
    }

    public function destroy(IncomeType $incomeType): JsonResponse
    {
        $deletedId = $incomeType->id;
        $incomeType->delete();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El tipo de ingreso se eliminó correctamente.'),
            'data' => [
                'deleted_id' => $deletedId,
            ],
        ]);
    }

    public function toggleStatus(Request $request, IncomeType $incomeType): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['A', 'I', 'T'])],
        ]);

        $incomeType->status = $validated['status'];
        $incomeType->save();

        $message = match ($validated['status']) {
            'A' => gettext('El tipo de ingreso se activó correctamente.'),
            'I' => gettext('El tipo de ingreso se desactivó correctamente.'),
            'T' => gettext('El tipo de ingreso se movió a la papelera.'),
            default => gettext('Estado actualizado correctamente.'),
        };

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => [
                'item' => $this->transformIncomeType($incomeType->refresh()),
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

    private function transformIncomeType(IncomeType $incomeType): array
    {
        return [
            'id' => $incomeType->id,
            'code' => $incomeType->code,
            'name' => $incomeType->name,
            'description' => $incomeType->description,
            'status' => $incomeType->status,
            'status_label' => $incomeType->status_label,
            'created_at' => optional($incomeType->created_at)->toIso8601String(),
            'updated_at' => optional($incomeType->updated_at)->toIso8601String(),
        ];
    }
}
