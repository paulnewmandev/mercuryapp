<?php

namespace App\Http\Controllers\Configuration;

use App\Contracts\SeoMetaManagerContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentCards\StorePaymentCardRequest;
use App\Http\Requests\PaymentCards\UpdatePaymentCardRequest;
use App\Models\PaymentCard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PaymentCardController extends Controller
{
    public function __construct(private readonly SeoMetaManagerContract $seoMetaManager)
    {
    }

    public function index(): View
    {
        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Tarjetas',
            'description' => 'Gestiona las tarjetas de pago utilizadas en tu empresa.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Configuración'), 'url' => route('configuration.company')],
            ['label' => gettext('Tarjetas')],
        ];

        return view('Configuration.Cards', compact('meta', 'breadcrumbItems'));
    }

    public function list(Request $request): JsonResponse
    {
        $sortBy = $request->string('sort_by', 'name')->toString();
        $sortDirection = $request->string('sort_direction', 'asc')->toString();
        $perPage = (int) $request->integer('per_page', 10);
        $search = Str::of($request->string('search')->toString())
            ->replaceMatches('/\s+/', ' ')
            ->trim();

        $allowedSorts = ['name', 'description', 'status', 'created_at'];
        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'name';
        }

        $sortDirection = Str::lower($sortDirection) === 'desc' ? 'desc' : 'asc';
        $allowedPerPage = [5, 10, 20, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $accentInsensitiveCollation = $this->resolveAccentInsensitiveCollation();
        $searchableColumns = ['name', 'description'];

        $tokens = $search->isEmpty()
            ? collect()
            : collect(explode(' ', Str::ascii(Str::lower($search))))
                ->filter()
                ->unique()
                ->values();

        $query = PaymentCard::query()
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
            ->through(fn (PaymentCard $card) => $this->transformPaymentCard($card));

        return response()->json([
            'status' => 'success',
            'message' => gettext('Tarjetas obtenidas correctamente.'),
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

    public function show(PaymentCard $card): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => gettext('Tarjeta obtenida correctamente.'),
            'data' => [
                'item' => $this->transformPaymentCard($card),
            ],
        ]);
    }

    public function store(StorePaymentCardRequest $request): JsonResponse
    {
        $user = Auth::user();

        $card = new PaymentCard($request->validated());
        $card->company()->associate($user->company_id);
        $card->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('La tarjeta se creó correctamente.'),
            'data' => [
                'item' => $this->transformPaymentCard($card->refresh()),
            ],
        ], Response::HTTP_CREATED);
    }

    public function update(UpdatePaymentCardRequest $request, PaymentCard $card): JsonResponse
    {
        $card->fill($request->validated());
        $card->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('La tarjeta se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformPaymentCard($card->refresh()),
            ],
        ]);
    }

    public function destroy(PaymentCard $card): JsonResponse
    {
        $deletedId = $card->id;
        $card->delete();

        return response()->json([
            'status' => 'success',
            'message' => gettext('La tarjeta se eliminó correctamente.'),
            'data' => [
                'deleted_id' => $deletedId,
            ],
        ]);
    }

    public function toggleStatus(Request $request, PaymentCard $card): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['A', 'I', 'T'])],
        ]);

        $card->status = $validated['status'];
        $card->save();

        $message = match ($validated['status']) {
            'A' => gettext('La tarjeta se activó correctamente.'),
            'I' => gettext('La tarjeta se desactivó correctamente.'),
            'T' => gettext('La tarjeta se movió a la papelera.'),
            default => gettext('Estado actualizado correctamente.'),
        };

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => [
                'item' => $this->transformPaymentCard($card->refresh()),
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

    private function transformPaymentCard(PaymentCard $card): array
    {
        return [
            'id' => $card->id,
            'name' => $card->name,
            'description' => $card->description,
            'status' => $card->status,
            'status_label' => $card->status_label,
            'created_at' => optional($card->created_at)->toIso8601String(),
            'updated_at' => optional($card->updated_at)->toIso8601String(),
        ];
    }
}
