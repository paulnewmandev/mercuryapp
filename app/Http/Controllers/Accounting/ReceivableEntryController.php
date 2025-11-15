<?php

namespace App\Http\Controllers\Accounting;

use App\Contracts\SeoMetaManagerContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\Receivables\StoreReceivableEntryRequest;
use App\Http\Requests\Accounting\Receivables\UpdateReceivableEntryRequest;
use App\Models\ReceivableCategory;
use App\Models\ReceivableEntry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ReceivableEntryController extends Controller
{
    public function __construct(private readonly SeoMetaManagerContract $seoMetaManager)
    {
    }

    public function index(): View
    {
        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Cuentas por cobrar',
            'description' => 'Gestiona las cuentas por cobrar de tu empresa.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Contabilidad')],
            ['label' => gettext('Cuentas por cobrar')],
        ];

        $categories = ReceivableCategory::query()
            ->where('status', 'A')
            ->orderBy('name')
            ->get()
            ->map(fn (ReceivableCategory $category) => [
                'id' => $category->id,
                'label' => $category->name,
            ]);

        return view('Accounting.Receivables', compact('meta', 'breadcrumbItems', 'categories'));
    }

    public function list(Request $request): JsonResponse
    {
        $sortBy = $request->string('sort_by', 'movement_date')->toString();
        $sortDirection = Str::lower($request->string('sort_direction', 'desc')->toString()) === 'asc' ? 'asc' : 'desc';
        $perPage = (int) $request->integer('per_page', 10);
        $search = Str::of($request->string('search')->toString())
            ->replaceMatches('/\s+/', ' ')
            ->trim();

        $allowedSorts = ['movement_date', 'concept', 'amount_cents', 'is_collected', 'created_at'];
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
            'receivable_entries.concept',
            'receivable_entries.description',
            'receivable_entries.reference',
            'receivable_categories.name',
        ];
        $grammar = DB::query()->getGrammar();

        $tokens = $search->isEmpty()
            ? collect()
            : collect(explode(' ', Str::ascii(Str::lower($search))))
                ->filter()
                ->unique()
                ->values();

        $categoryFilter = $request->string('receivable_category_id')->toString();
        $statusFilter = $request->string('is_collected')->toString();

        $query = ReceivableEntry::query()
            ->with('category')
            ->leftJoin('receivable_categories', 'receivable_categories.id', '=', 'receivable_entries.receivable_category_id')
            ->when($categoryFilter !== '', fn ($builder) => $builder->where('receivable_category_id', $categoryFilter))
            ->when($statusFilter !== '', fn ($builder) => $builder->where('is_collected', $statusFilter === '1'))
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
            ->select('receivable_entries.*')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (ReceivableEntry $entry) => $this->transformEntry($entry));

        return response()->json([
            'status' => 'success',
            'message' => gettext('Cuentas por cobrar obtenidas correctamente.'),
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

    public function show(ReceivableEntry $receivable): JsonResponse
    {
        $receivable->load('category');

        return response()->json([
            'status' => 'success',
            'message' => gettext('Cuenta por cobrar obtenida correctamente.'),
            'data' => [
                'item' => $this->transformEntry($receivable),
            ],
        ]);
    }

    public function store(StoreReceivableEntryRequest $request): JsonResponse
    {
        $user = Auth::user();
        $data = $request->validated();

        $entry = new ReceivableEntry([
            'receivable_category_id' => $data['receivable_category_id'],
            'movement_date' => $data['movement_date'],
            'concept' => $data['concept'],
            'description' => $data['description'] ?? null,
            'amount_cents' => $data['amount_cents'],
            'currency_code' => $data['currency_code'] ?? 'USD',
            'reference' => $data['reference'] ?? null,
            'is_collected' => $data['is_collected'] ?? false,
            'collected_at' => $data['collected_at'] ?? null,
        ]);

        $entry->company()->associate($user->company_id);
        $entry->category()->associate(ReceivableCategory::query()->findOrFail($data['receivable_category_id']));
        $entry->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('La cuenta por cobrar se registró correctamente.'),
            'data' => [
                'item' => $this->transformEntry($entry->refresh()->load('category')),
            ],
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateReceivableEntryRequest $request, ReceivableEntry $receivable): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['receivable_category_id'])) {
            $receivable->category()->associate(ReceivableCategory::query()->findOrFail($data['receivable_category_id']));
        }

        if (isset($data['amount_cents'])) {
            $receivable->amount_cents = $data['amount_cents'];
        }

        if (array_key_exists('is_collected', $data)) {
            $receivable->is_collected = (bool) $data['is_collected'];
            $receivable->collected_at = $receivable->is_collected ? now() : null;
        }

        $receivable->fill(collect($data)->except([
            'receivable_category_id',
            'amount',
            'amount_cents',
            'is_collected',
            'collected_at',
        ])->toArray());

        $receivable->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('La cuenta por cobrar se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformEntry($receivable->refresh()->load('category')),
            ],
        ]);
    }

    public function destroy(ReceivableEntry $receivable): JsonResponse
    {
        $deletedId = $receivable->id;
        $receivable->delete();

        return response()->json([
            'status' => 'success',
            'message' => gettext('La cuenta por cobrar se eliminó correctamente.'),
            'data' => [
                'deleted_id' => $deletedId,
            ],
        ]);
    }

    public function toggleSettlement(Request $request, ReceivableEntry $receivable): JsonResponse
    {
        $validated = $request->validate([
            'is_collected' => ['required', 'boolean'],
        ]);

        $receivable->is_collected = (bool) $validated['is_collected'];
        $receivable->collected_at = $receivable->is_collected ? now() : null;
        $receivable->save();

        $message = $receivable->is_collected
            ? gettext('La cuenta por cobrar fue marcada como cobrada.')
            : gettext('La cuenta por cobrar fue marcada como pendiente.');

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => [
                'item' => $this->transformEntry($receivable->refresh()->load('category')),
            ],
        ]);
    }

    public function options(): JsonResponse
    {
        $categories = ReceivableCategory::query()
            ->where('status', 'A')
            ->orderBy('name')
            ->get()
            ->map(fn (ReceivableCategory $category) => [
                'id' => $category->id,
                'label' => $category->name,
            ]);

        return response()->json([
            'status' => 'success',
            'message' => gettext('Categorías de cuentas por cobrar obtenidas correctamente.'),
            'data' => [
                'items' => $categories,
            ],
        ]);
    }

    private function transformEntry(ReceivableEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'receivable_category_id' => $entry->receivable_category_id,
            'receivable_category_name' => optional($entry->category)->name,
            'movement_date' => optional($entry->movement_date)?->toDateString(),
            'movement_date_formatted' => $entry->movement_date_formatted,
            'concept' => $entry->concept,
            'description' => $entry->description,
            'reference' => $entry->reference,
            'amount_cents' => $entry->amount_cents,
            'amount_formatted' => $entry->amount_formatted,
            'currency_code' => $entry->currency_code,
            'status' => $entry->is_collected ? 'A' : 'I',
            'is_collected' => (bool) $entry->is_collected,
            'status_label' => $entry->status_label,
            'collected_at' => optional($entry->collected_at)?->toIso8601String(),
            'collected_at_label' => optional($entry->collected_at)?->translatedFormat('d F Y H:i'),
            'created_at' => optional($entry->created_at)?->toIso8601String(),
            'updated_at' => optional($entry->updated_at)?->toIso8601String(),
        ];
    }

    private function resolveAccentInsensitiveCollation(): string
    {
        return config('database.connections.mysql.search_collation', 'utf8mb4_0900_ai_ci');
    }
}

