<?php

namespace App\Http\Controllers\Accounting;

use App\Contracts\SeoMetaManagerContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\Expenses\StoreExpenseRequest;
use App\Http\Requests\Accounting\Expenses\UpdateExpenseRequest;
use App\Models\Expense;
use App\Models\ExpenseType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    public function __construct(private readonly SeoMetaManagerContract $seoMetaManager)
    {
    }

    public function index(): View
    {
        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Egresos',
            'description' => 'Registra y controla los egresos de tu empresa.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Contabilidad')],
            ['label' => gettext('Egresos')],
        ];

        $expenseTypes = ExpenseType::query()
            ->where('status', 'A')
            ->orderBy('name')
            ->get()
            ->map(fn (ExpenseType $type) => [
                'id' => $type->id,
                'label' => $type->name,
            ]);

        return view('Accounting.Expenses', compact('meta', 'breadcrumbItems', 'expenseTypes'));
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
            'expenses.concept',
            'expenses.description',
            'expenses.reference',
            'expense_types.name',
        ];
        $grammar = DB::query()->getGrammar();

        $tokens = $search->isEmpty()
            ? collect()
            : collect(explode(' ', Str::ascii(Str::lower($search))))
                ->filter()
                ->unique()
                ->values();

        $typeFilter = $request->string('expense_type_id')->toString();
        $statusFilter = $request->string('status')->toString();

        $query = Expense::query()
            ->with('type')
            ->leftJoin('expense_types', 'expense_types.id', '=', 'expenses.expense_type_id')
            ->when($typeFilter !== '', fn ($builder) => $builder->where('expense_type_id', $typeFilter))
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
            ->select('expenses.*')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Expense $expense) => $this->transformExpense($expense));

        return response()->json([
            'status' => 'success',
            'message' => gettext('Egresos obtenidos correctamente.'),
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

    public function show(Expense $expense): JsonResponse
    {
        $expense->load('type');

        return response()->json([
            'status' => 'success',
            'message' => gettext('Egreso obtenido correctamente.'),
            'data' => [
                'item' => $this->transformExpense($expense),
            ],
        ]);
    }

    public function store(StoreExpenseRequest $request): JsonResponse
    {
        $user = Auth::user();
        $data = $request->validated();

        $expense = new Expense([
            'expense_type_id' => $data['expense_type_id'],
            'movement_date' => $data['movement_date'],
            'concept' => $data['concept'],
            'description' => $data['description'] ?? null,
            'amount_cents' => $data['amount_cents'],
            'currency_code' => $data['currency_code'] ?? 'USD',
            'reference' => $data['reference'] ?? null,
            'status' => $data['status'] ?? 'A',
        ]);

        $expense->company()->associate($user->company_id);
        $expense->type()->associate(ExpenseType::query()->findOrFail($data['expense_type_id']));
        $expense->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El egreso se registró correctamente.'),
            'data' => [
                'item' => $this->transformExpense($expense->refresh()->load('type')),
            ],
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['expense_type_id'])) {
            $expense->type()->associate(ExpenseType::query()->findOrFail($data['expense_type_id']));
        }

        if (isset($data['amount_cents'])) {
            $expense->amount_cents = $data['amount_cents'];
        }

        $expense->fill(collect($data)->except(['expense_type_id', 'amount', 'amount_cents'])->toArray());
        $expense->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El egreso se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformExpense($expense->refresh()->load('type')),
            ],
        ]);
    }

    public function destroy(Expense $expense): JsonResponse
    {
        $deletedId = $expense->id;
        $expense->delete();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El egreso se eliminó correctamente.'),
            'data' => [
                'deleted_id' => $deletedId,
            ],
        ]);
    }

    public function toggleStatus(Request $request, Expense $expense): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['A', 'I', 'T'])],
        ]);

        $expense->status = $validated['status'];
        $expense->save();

        $message = match ($validated['status']) {
            'A' => gettext('El egreso se activó correctamente.'),
            'I' => gettext('El egreso se desactivó correctamente.'),
            'T' => gettext('El egreso se movió a la papelera.'),
            default => gettext('Estado actualizado correctamente.'),
        };

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => [
                'item' => $this->transformExpense($expense->refresh()->load('type')),
            ],
        ]);
    }

    public function options(): JsonResponse
    {
        $expenseTypes = ExpenseType::query()
            ->where('status', 'A')
            ->orderBy('name')
            ->get()
            ->map(fn (ExpenseType $type) => [
                'id' => $type->id,
                'label' => $type->name,
            ]);

        return response()->json([
            'status' => 'success',
            'message' => gettext('Tipos de egresos obtenidos correctamente.'),
            'data' => [
                'items' => $expenseTypes,
            ],
        ]);
    }

    private function transformExpense(Expense $expense): array
    {
        return [
            'id' => $expense->id,
            'expense_type_id' => $expense->expense_type_id,
            'expense_type_name' => optional($expense->type)->name,
            'movement_date' => optional($expense->movement_date)?->toDateString(),
            'movement_date_formatted' => $expense->movement_date_formatted,
            'concept' => $expense->concept,
            'description' => $expense->description,
            'reference' => $expense->reference,
            'amount_cents' => $expense->amount_cents,
            'amount_formatted' => $expense->amount_formatted,
            'currency_code' => $expense->currency_code,
            'status' => $expense->status,
            'status_label' => $expense->status_label,
            'created_at' => optional($expense->created_at)?->toIso8601String(),
            'updated_at' => optional($expense->updated_at)?->toIso8601String(),
        ];
    }

    private function resolveAccentInsensitiveCollation(): string
    {
        return config('database.connections.mysql.search_collation', 'utf8mb4_unicode_ci');
    }
}


