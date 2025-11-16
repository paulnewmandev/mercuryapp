<?php

namespace App\Http\Controllers\Configuration;

use App\Contracts\SeoMetaManagerContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\BankAccounts\StoreBankAccountRequest;
use App\Http\Requests\BankAccounts\UpdateBankAccountRequest;
use App\Models\BankAccount;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BankAccountController extends Controller
{
    public function __construct(private readonly SeoMetaManagerContract $seoMetaManager)
    {
    }

    public function index(): View
    {
        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Cuentas de banco',
            'description' => 'Administra las cuentas bancarias asociadas a tu empresa.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Configuración'), 'url' => route('configuration.company')],
            ['label' => gettext('Cuentas de banco')],
        ];

        return view('Configuration.BankAccounts', compact('meta', 'breadcrumbItems'));
    }

    public function list(Request $request): JsonResponse
    {
        $sortBy = $request->string('sort_by', 'bank_name')->toString();
        $sortDirection = Str::lower($request->string('sort_direction', 'asc')->toString()) === 'desc' ? 'desc' : 'asc';
        $perPage = (int) $request->integer('per_page', 10);
        $search = Str::of($request->string('search')->toString())
            ->replaceMatches('/\s+/', ' ')
            ->trim();

        $allowedSorts = ['bank_name', 'account_number', 'account_type', 'account_holder_name', 'alias', 'status', 'created_at'];
        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'bank_name';
        }

        $allowedPerPage = [5, 10, 20, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $accentInsensitiveCollation = $this->resolveAccentInsensitiveCollation();
        $searchableColumns = ['bank_name', 'account_number', 'account_type', 'account_holder_name', 'alias'];

        $tokens = $search->isEmpty()
            ? collect()
            : collect(explode(' ', Str::ascii(Str::lower($search))))
                ->filter()
                ->unique()
                ->values();

        $query = BankAccount::query()
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
            ->through(fn (BankAccount $account) => $this->transformBankAccount($account));

        return response()->json([
            'status' => 'success',
            'message' => gettext('Cuentas de banco obtenidas correctamente.'),
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

    public function show(BankAccount $account): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => gettext('Cuenta de banco obtenida correctamente.'),
            'data' => [
                'item' => $this->transformBankAccount($account),
            ],
        ]);
    }

    public function store(StoreBankAccountRequest $request): JsonResponse
    {
        $user = Auth::user();

        $account = new BankAccount($request->validated());
        $account->company()->associate($user->company_id);
        $account->status = $account->status ?? 'A';
        $account->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('La cuenta de banco se creó correctamente.'),
            'data' => [
                'item' => $this->transformBankAccount($account->refresh()),
            ],
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateBankAccountRequest $request, BankAccount $account): JsonResponse
    {
        $account->fill($request->validated());
        $account->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('La cuenta de banco se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformBankAccount($account->refresh()),
            ],
        ]);
    }

    public function destroy(BankAccount $account): JsonResponse
    {
        $deletedId = $account->id;
        $account->delete();

        return response()->json([
            'status' => 'success',
            'message' => gettext('La cuenta de banco se eliminó correctamente.'),
            'data' => [
                'deleted_id' => $deletedId,
            ],
        ]);
    }

    public function toggleStatus(Request $request, BankAccount $account): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['A', 'I', 'T'])],
        ]);

        $account->status = $validated['status'];
        $account->save();

        $message = match ($validated['status']) {
            'A' => gettext('La cuenta de banco se activó correctamente.'),
            'I' => gettext('La cuenta de banco se desactivó correctamente.'),
            'T' => gettext('La cuenta de banco se movió a la papelera.'),
            default => gettext('Estado actualizado correctamente.'),
        };

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => [
                'item' => $this->transformBankAccount($account->refresh()),
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

    private function transformBankAccount(BankAccount $account): array
    {
        return [
            'id' => $account->id,
            'bank_name' => $account->bank_name,
            'account_number' => $account->account_number,
            'account_type' => $account->account_type,
            'account_type_label' => $this->resolveAccountTypeLabel($account->account_type),
            'account_holder_name' => $account->account_holder_name,
            'alias' => $account->alias,
            'status' => $account->status,
            'status_label' => $account->status_label,
            'created_at' => optional($account->created_at)->toIso8601String(),
            'updated_at' => optional($account->updated_at)->toIso8601String(),
        ];
    }

    private function resolveAccountTypeLabel(?string $type): string
    {
        return match ($type) {
            'ahorros' => gettext('Cuenta de ahorros'),
            'corriente' => gettext('Cuenta corriente'),
            default => $type ? ucfirst($type) : '-',
        };
    }
}
