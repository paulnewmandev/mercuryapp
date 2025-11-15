<?php

namespace App\Http\Controllers\Accounting;

use App\Contracts\SeoMetaManagerContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\Incomes\StoreIncomeRequest;
use App\Http\Requests\Accounting\Incomes\UpdateIncomeRequest;
use App\Models\Income;
use App\Models\IncomeType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class IncomeController extends Controller
{
    public function __construct(private readonly SeoMetaManagerContract $seoMetaManager)
    {
    }

    public function index(): View
    {
        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Ingresos',
            'description' => 'Registra y controla los ingresos de tu empresa.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Contabilidad')],
            ['label' => gettext('Ingresos')],
        ];

        $incomeTypes = IncomeType::query()
            ->where('status', 'A')
            ->orderBy('name')
            ->get()
            ->map(fn (IncomeType $type) => [
                'id' => $type->id,
                'label' => $type->name,
            ]);

        return view('Accounting.Incomes', compact('meta', 'breadcrumbItems', 'incomeTypes'));
    }

    public function list(Request $request): JsonResponse
    {
        $sortBy = $request->string('sort_by', 'movement_date')->toString();
        $sortDirection = Str::lower($request->string('sort_direction', 'desc')->toString()) === 'asc' ? 'asc' : 'desc';
        $perPage = (int) $request->integer('per_page', 10);
        $search = Str::of($request->string('search')->toString())
            ->replaceMatches('/\s+/', ' ')
            ->trim();

        $allowedSorts = ['movement_date', 'concept', 'amount_cents', 'status', 'created_at'];
        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'movement_date';
            $sortDirection = 'desc';
        }

        $allowedPerPage = [5, 10, 20, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $accentInsensitiveCollation = $this->resolveAccentInsensitiveCollation();
        $searchableColumns = [
            'incomes.concept',
            'incomes.description',
            'incomes.reference',
            'income_types.name',
        ];
        $grammar = DB::query()->getGrammar();

        $tokens = $search->isEmpty()
            ? collect()
            : collect(explode(' ', Str::ascii(Str::lower($search))))
                ->filter()
                ->unique()
                ->values();

        $typeFilter = $request->string('income_type_id')->toString();
        $statusFilter = $request->string('status')->toString();

        $query = Income::query()
            ->with('type')
            ->leftJoin('income_types', 'income_types.id', '=', 'incomes.income_type_id')
            ->when($typeFilter !== '', fn ($builder) => $builder->where('income_type_id', $typeFilter))
            ->when($statusFilter !== '', fn ($builder) => $builder->where('status', $statusFilter))
            ->when($request->filled('from_date'), fn ($builder) => $builder->whereDate('movement_date', '>=', $request->date('from_date')))
            ->when($request->filled('to_date'), fn ($builder) => $builder->whereDate('movement_date', '<=', $request->date('to_date')))
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
            ->select('incomes.*')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Income $income) => $this->transformIncome($income));

        return response()->json([
            'status' => 'success',
            'message' => gettext('Ingresos obtenidos correctamente.'),
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

    public function show(Income $income): JsonResponse
    {
        $income->load('type');

        return response()->json([
            'status' => 'success',
            'message' => gettext('Ingreso obtenido correctamente.'),
            'data' => [
                'item' => $this->transformIncome($income),
            ],
        ]);
    }

    public function store(StoreIncomeRequest $request): JsonResponse
    {
        $user = Auth::user();
        $data = $request->validated();

        $income = new Income([
            'income_type_id' => $data['income_type_id'],
            'movement_date' => $data['movement_date'],
            'concept' => $data['concept'],
            'description' => $data['description'] ?? null,
            'amount_cents' => $data['amount_cents'],
            'currency_code' => $data['currency_code'] ?? 'USD',
            'reference' => $data['reference'] ?? null,
            'status' => $data['status'] ?? 'A',
        ]);

        $income->company()->associate($user->company_id);
        $income->type()->associate(IncomeType::query()->findOrFail($data['income_type_id']));
        $income->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El ingreso se registró correctamente.'),
            'data' => [
                'item' => $this->transformIncome($income->refresh()->load('type')),
            ],
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateIncomeRequest $request, Income $income): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['income_type_id'])) {
            $income->type()->associate(IncomeType::query()->findOrFail($data['income_type_id']));
        }

        if (isset($data['amount_cents'])) {
            $income->amount_cents = $data['amount_cents'];
        }

        $income->fill(collect($data)->except(['income_type_id', 'amount', 'amount_cents'])->toArray());
        $income->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El ingreso se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformIncome($income->refresh()->load('type')),
            ],
        ]);
    }

    public function destroy(Income $income): JsonResponse
    {
        $deletedId = $income->id;
        $income->delete();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El ingreso se eliminó correctamente.'),
            'data' => [
                'deleted_id' => $deletedId,
            ],
        ]);
    }

    public function toggleStatus(Request $request, Income $income): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['A', 'I', 'T'])],
        ]);

        $income->status = $validated['status'];
        $income->save();

        $message = match ($validated['status']) {
            'A' => gettext('El ingreso se activó correctamente.'),
            'I' => gettext('El ingreso se desactivó correctamente.'),
            'T' => gettext('El ingreso se movió a la papelera.'),
            default => gettext('Estado actualizado correctamente.'),
        };

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => [
                'item' => $this->transformIncome($income->refresh()->load('type')),
            ],
        ]);
    }

    public function options(): JsonResponse
    {
        $incomeTypes = IncomeType::query()
            ->where('status', 'A')
            ->orderBy('name')
            ->get()
            ->map(fn (IncomeType $type) => [
                'id' => $type->id,
                'label' => $type->name,
            ]);

        return response()->json([
            'status' => 'success',
            'message' => gettext('Tipos de ingresos obtenidos correctamente.'),
            'data' => [
                'items' => $incomeTypes,
            ],
        ]);
    }

    private function transformIncome(Income $income): array
    {
        return [
            'id' => $income->id,
            'income_type_id' => $income->income_type_id,
            'income_type_name' => optional($income->type)->name,
            'movement_date' => optional($income->movement_date)?->toDateString(),
            'movement_date_formatted' => $income->movement_date_formatted,
            'concept' => $income->concept,
            'description' => $income->description,
            'reference' => $income->reference,
            'amount_cents' => $income->amount_cents,
            'amount_formatted' => $income->amount_formatted,
            'currency_code' => $income->currency_code,
            'status' => $income->status,
            'status_label' => $income->status_label,
            'created_at' => optional($income->created_at)?->toIso8601String(),
            'updated_at' => optional($income->updated_at)?->toIso8601String(),
        ];
    }

    private function resolveAccentInsensitiveCollation(): string
    {
        return config('database.connections.mysql.search_collation', 'utf8mb4_0900_ai_ci');
    }
}


