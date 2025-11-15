<?php

namespace App\Http\Controllers\Configuration;

use App\Contracts\SeoMetaManagerContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExpenseTypes\StoreExpenseTypeRequest;
use App\Http\Requests\ExpenseTypes\UpdateExpenseTypeRequest;
use App\Models\ExpenseType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ExpenseTypeController extends Controller
{
    public function __construct(private readonly SeoMetaManagerContract $seoMetaManager)
    {
    }

    public function index(): View
    {
        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Tipos de egresos',
            'description' => 'Administra las categorías de egresos de tu empresa.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Configuración'), 'url' => route('configuration.company')],
            ['label' => gettext('Tipos de egresos')],
        ];

        return view('Configuration.ExpenseTypes', compact('meta', 'breadcrumbItems'));
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

        $query = ExpenseType::query()
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
            ->through(fn (ExpenseType $type) => $this->transformExpenseType($type));

        return response()->json([
            'status' => 'success',
            'message' => gettext('Tipos de egresos obtenidos correctamente.'),
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

    public function show(ExpenseType $expenseType): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => gettext('Tipo de egreso obtenido correctamente.'),
            'data' => [
                'item' => $this->transformExpenseType($expenseType),
            ],
        ]);
    }

    public function store(StoreExpenseTypeRequest $request): JsonResponse
    {
        $user = Auth::user();

        $expenseType = new ExpenseType($request->validated());
        $expenseType->company()->associate($user->company_id);
        $expenseType->status = $expenseType->status ?? 'A';
        $expenseType->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El tipo de egreso se creó correctamente.'),
            'data' => [
                'item' => $this->transformExpenseType($expenseType->refresh()),
            ],
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateExpenseTypeRequest $request, ExpenseType $expenseType): JsonResponse
    {
        $expenseType->fill($request->validated());
        $expenseType->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El tipo de egreso se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformExpenseType($expenseType->refresh()),
            ],
        ]);
    }

    public function destroy(ExpenseType $expenseType): JsonResponse
    {
        $deletedId = $expenseType->id;
        $expenseType->delete();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El tipo de egreso se eliminó correctamente.'),
            'data' => [
                'deleted_id' => $deletedId,
            ],
        ]);
    }

    public function toggleStatus(Request $request, ExpenseType $expenseType): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['A', 'I', 'T'])],
        ]);

        $expenseType->status = $validated['status'];
        $expenseType->save();

        $message = match ($validated['status']) {
            'A' => gettext('El tipo de egreso se activó correctamente.'),
            'I' => gettext('El tipo de egreso se desactivó correctamente.'),
            'T' => gettext('El tipo de egreso se movió a la papelera.'),
            default => gettext('Estado actualizado correctamente.'),
        };

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => [
                'item' => $this->transformExpenseType($expenseType->refresh()),
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

    private function transformExpenseType(ExpenseType $expenseType): array
    {
        return [
            'id' => $expenseType->id,
            'code' => $expenseType->code,
            'name' => $expenseType->name,
            'description' => $expenseType->description,
            'status' => $expenseType->status,
            'status_label' => $expenseType->status_label,
            'created_at' => optional($expenseType->created_at)->toIso8601String(),
            'updated_at' => optional($expenseType->updated_at)->toIso8601String(),
        ];
    }
}
