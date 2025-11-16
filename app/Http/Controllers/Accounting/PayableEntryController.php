<?php

namespace App\Http\Controllers\Accounting;

use App\Contracts\SeoMetaManagerContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\Payables\StorePayableEntryRequest;
use App\Http\Requests\Accounting\Payables\UpdatePayableEntryRequest;
use App\Models\PayableCategory;
use App\Models\PayableEntry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PayableEntryController extends Controller
{
    public function __construct(private readonly SeoMetaManagerContract $seoMetaManager)
    {
    }

    public function index(): View
    {
        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Cuentas por pagar',
            'description' => 'Gestiona las cuentas por pagar de tu empresa.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Contabilidad')],
            ['label' => gettext('Cuentas por pagar')],
        ];

        $categories = PayableCategory::query()
            ->where('status', 'A')
            ->orderBy('name')
            ->get()
            ->map(fn (PayableCategory $category) => [
                'id' => $category->id,
                'label' => $category->name,
            ]);

        return view('Accounting.Payables', compact('meta', 'breadcrumbItems', 'categories'));
    }

    public function list(Request $request): JsonResponse
    {
        $sortBy = $request->string('sort_by', 'movement_date')->toString();
        $sortDirection = Str::lower($request->string('sort_direction', 'desc')->toString()) === 'asc' ? 'asc' : 'desc';
        $perPage = (int) $request->integer('per_page', 10);
        $search = Str::of($request->string('search')->toString())
            ->replaceMatches('/\s+/', ' ')
            ->trim();

        $allowedSorts = ['movement_date', 'concept', 'amount_cents', 'is_paid', 'created_at'];
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
            'payable_entries.concept',
            'payable_entries.description',
            'payable_entries.reference',
            'payable_categories.name',
        ];
        $grammar = DB::query()->getGrammar();

        $tokens = $search->isEmpty()
            ? collect()
            : collect(explode(' ', Str::ascii(Str::lower($search))))
                ->filter()
                ->unique()
                ->values();

        $categoryFilter = $request->string('payable_category_id')->toString();
        $statusFilter = $request->string('is_paid')->toString();

        $query = PayableEntry::query()
            ->with('category')
            ->leftJoin('payable_categories', 'payable_categories.id', '=', 'payable_entries.payable_category_id')
            ->when($categoryFilter !== '', fn ($builder) => $builder->where('payable_category_id', $categoryFilter))
            ->when($statusFilter !== '', fn ($builder) => $builder->where('is_paid', $statusFilter === '1'))
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
                        });
                    });
                });
            })
            ->orderBy($sortBy, $sortDirection);

        $paginator = $query
            ->select('payable_entries.*')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (PayableEntry $entry) => $this->transformEntry($entry));

        return response()->json([
            'status' => 'success',
            'message' => gettext('Cuentas por pagar obtenidas correctamente.'),
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

    public function show(PayableEntry $payable): JsonResponse
    {
        $payable->load('category');

        return response()->json([
            'status' => 'success',
            'message' => gettext('Cuenta por pagar obtenida correctamente.'),
            'data' => [
                'item' => $this->transformEntry($payable),
            ],
        ]);
    }

    public function store(StorePayableEntryRequest $request): JsonResponse
    {
        $user = Auth::user();
        $data = $request->validated();

        $entry = new PayableEntry([
            'payable_category_id' => $data['payable_category_id'],
            'movement_date' => $data['movement_date'],
            'concept' => $data['concept'],
            'description' => $data['description'] ?? null,
            'amount_cents' => $data['amount_cents'],
            'currency_code' => $data['currency_code'] ?? 'USD',
            'reference' => $data['reference'] ?? null,
            'is_paid' => $data['is_paid'] ?? false,
            'paid_at' => $data['paid_at'] ?? null,
        ]);

        $entry->company()->associate($user->company_id);
        $entry->category()->associate(PayableCategory::query()->findOrFail($data['payable_category_id']));
        $entry->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('La cuenta por pagar se registró correctamente.'),
            'data' => [
                'item' => $this->transformEntry($entry->refresh()->load('category')),
            ],
        ], Response::HTTP_CREATED);
    }

    public function update(UpdatePayableEntryRequest $request, PayableEntry $payable): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['payable_category_id'])) {
            $payable->category()->associate(PayableCategory::query()->findOrFail($data['payable_category_id']));
        }

        if (isset($data['amount_cents'])) {
            $payable->amount_cents = $data['amount_cents'];
        }

        if (array_key_exists('is_paid', $data)) {
            $payable->is_paid = (bool) $data['is_paid'];
            $payable->paid_at = $payable->is_paid ? now() : null;
        }

        $payable->fill(collect($data)->except([
            'payable_category_id',
            'amount',
            'amount_cents',
            'is_paid',
            'paid_at',
        ])->toArray());

        $payable->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('La cuenta por pagar se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformEntry($payable->refresh()->load('category')),
            ],
        ]);
    }

    public function destroy(PayableEntry $payable): JsonResponse
    {
        $deletedId = $payable->id;
        $payable->delete();

        return response()->json([
            'status' => 'success',
            'message' => gettext('La cuenta por pagar se eliminó correctamente.'),
            'data' => [
                'deleted_id' => $deletedId,
            ],
        ]);
    }

    public function toggleSettlement(Request $request, PayableEntry $payable): JsonResponse
    {
        $validated = $request->validate([
            'is_paid' => ['required', 'boolean'],
        ]);

        $payable->is_paid = (bool) $validated['is_paid'];
        $payable->paid_at = $payable->is_paid ? now() : null;
        $payable->save();

        $message = $payable->is_paid
            ? gettext('La cuenta por pagar fue marcada como pagada.')
            : gettext('La cuenta por pagar fue marcada como pendiente.');

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => [
                'item' => $this->transformEntry($payable->refresh()->load('category')),
            ],
        ]);
    }

    public function options(): JsonResponse
    {
        $categories = PayableCategory::query()
            ->where('status', 'A')
            ->orderBy('name')
            ->get()
            ->map(fn (PayableCategory $category) => [
                'id' => $category->id,
                'label' => $category->name,
            ]);

        return response()->json([
            'status' => 'success',
            'message' => gettext('Categorías de cuentas por pagar obtenidas correctamente.'),
            'data' => [
                'items' => $categories,
            ],
        ]);
    }

    private function transformEntry(PayableEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'payable_category_id' => $entry->payable_category_id,
            'payable_category_name' => optional($entry->category)->name,
            'movement_date' => optional($entry->movement_date)?->toDateString(),
            'movement_date_formatted' => $entry->movement_date_formatted,
            'concept' => $entry->concept,
            'description' => $entry->description,
            'reference' => $entry->reference,
            'amount_cents' => $entry->amount_cents,
            'amount_formatted' => $entry->amount_formatted,
            'currency_code' => $entry->currency_code,
            'status' => $entry->is_paid ? 'A' : 'I',
            'is_paid' => (bool) $entry->is_paid,
            'status_label' => $entry->status_label,
            'paid_at' => optional($entry->paid_at)?->toIso8601String(),
            'paid_at_label' => optional($entry->paid_at)?->translatedFormat('d F Y H:i'),
            'created_at' => optional($entry->created_at)?->toIso8601String(),
            'updated_at' => optional($entry->updated_at)?->toIso8601String(),
        ];
    }

    private function resolveAccentInsensitiveCollation(): string
    {
        return config('database.connections.mysql.search_collation', 'utf8mb4_unicode_ci');
    }
}

