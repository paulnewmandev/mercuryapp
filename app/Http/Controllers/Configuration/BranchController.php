<?php

namespace App\Http\Controllers\Configuration;

use App\Contracts\SeoMetaManagerContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\Branches\StoreBranchRequest;
use App\Http\Requests\Branches\UpdateBranchRequest;
use App\Models\Branch;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;

/**
 * Controlador para la gestión de sucursales dentro del módulo de configuración.
 */
class BranchController extends Controller
{
    /**
     * Constructor.
     *
     * @param SeoMetaManagerContract $seoMetaManager
     */
    public function __construct(private readonly SeoMetaManagerContract $seoMetaManager)
    {
    }

    /**
     * Muestra la vista principal del listado de sucursales.
     *
     * @return View
     */
    public function index(): View
    {
        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Sucursales',
            'description' => 'Administra las sucursales de tu empresa en MercuryApp.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Configuración'), 'url' => route('configuration.company')],
            ['label' => gettext('Sucursales')],
        ];

        return view('Configuration.Branches', [
            'meta' => $meta,
            'breadcrumbItems' => $breadcrumbItems,
        ]);
    }

    /**
     * Devuelve el listado paginado de sucursales.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        $sortBy = $request->string('sort_by', 'name')->toString();
        $sortDirection = $request->string('sort_direction', 'asc')->toString();
        $perPage = (int) $request->integer('per_page', 10);
        $search = Str::of($request->string('search')->toString())
            ->replaceMatches('/\s+/', ' ')
            ->trim();

        $allowedSorts = ['code', 'name', 'email', 'phone_number', 'status', 'created_at'];
        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'name';
        }

        $sortDirection = Str::lower($sortDirection) === 'desc' ? 'desc' : 'asc';
        $allowedPerPage = [5, 10, 20, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $accentInsensitiveCollation = $this->resolveAccentInsensitiveCollation();
        $searchableColumns = ['code', 'name', 'address', 'website', 'email', 'phone_number'];

        $tokens = $search->isEmpty()
            ? collect()
            : collect(explode(' ', Str::ascii(Str::lower($search))))
                ->filter()
                ->unique()
                ->values();

        $query = Branch::query()
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
            ->through(fn (Branch $branch) => $this->transformBranch($branch));

        return response()->json([
            'status' => 'success',
            'message' => gettext('Sucursales obtenidas correctamente.'),
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

    /**
     * Devuelve el detalle de una sucursal específica.
     *
     * @param Branch $branch
     *
     * @return JsonResponse
     */
    public function show(Branch $branch): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => gettext('Sucursal obtenida correctamente.'),
            'data' => [
                'item' => $this->transformBranch($branch),
            ],
        ]);
    }

    /**
     * Almacena una nueva sucursal.
     *
     * @param StoreBranchRequest $request
     *
     * @return JsonResponse
     */
    public function store(StoreBranchRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $branch = new Branch($request->validated());
        $branch->company()->associate($user->company_id);
        $branch->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('La sucursal se creó correctamente.'),
            'data' => [
                'item' => $this->transformBranch($branch->refresh()),
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * Actualiza una sucursal existente.
     *
     * @param UpdateBranchRequest $request
     * @param Branch $branch
     *
     * @return JsonResponse
     */
    public function update(UpdateBranchRequest $request, Branch $branch): JsonResponse
    {
        $branch->fill($request->validated());
        $branch->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('La sucursal se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformBranch($branch->refresh()),
            ],
        ]);
    }

    /**
     * Elimina una sucursal de forma definitiva.
     *
     * @param Branch $branch
     *
     * @return JsonResponse
     */
    public function destroy(Branch $branch): JsonResponse
    {
        $deletedId = $branch->id;

        try {
        $branch->delete();

        return response()->json([
            'status' => 'success',
            'message' => gettext('La sucursal se eliminó correctamente.'),
            'data' => [
                'deleted_id' => $deletedId,
            ],
        ]);
        } catch (QueryException $exception) {
            report($exception);

            return response()->json([
                'status' => 'error',
                'message' => gettext('No es posible eliminar la sucursal porque tiene movimientos o documentos asociados. Puedes desactivarla para ocultarla del sistema.'),
                'data' => null,
            ], Response::HTTP_CONFLICT);
        }
    }

    /**
     * Actualiza el estado activo/inactivo de una sucursal.
     *
     * @param Request $request
     * @param Branch $branch
     *
     * @return JsonResponse
     */
    public function toggleStatus(Request $request, Branch $branch): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['A', 'I', 'T'])],
        ]);

        $branch->status = $validated['status'];
        $branch->save();

        $message = match ($validated['status']) {
            'A' => gettext('La sucursal se activó correctamente.'),
            'I' => gettext('La sucursal se desactivó correctamente.'),
            'T' => gettext('La sucursal se movió a la papelera.'),
            default => gettext('Estado actualizado correctamente.'),
        };

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => [
                'item' => $this->transformBranch($branch->refresh()),
            ],
        ]);
    }

    /**
     * Transforma una sucursal en la estructura estándar de respuesta.
     *
     * @param Branch $branch
     *
     * @return array<string, mixed>
     */
    private function transformBranch(Branch $branch): array
    {
        return [
            'id' => $branch->id,
            'code' => $branch->code,
            'name' => $branch->name,
            'address' => $branch->address,
            'website' => $branch->website,
            'email' => $branch->email,
            'phone_number' => $branch->phone_number,
            'latitude' => $branch->latitude,
            'longitude' => $branch->longitude,
            'status' => $branch->status,
            'status_label' => $branch->status_label,
            'created_at' => optional($branch->created_at)->toIso8601String(),
            'updated_at' => optional($branch->updated_at)->toIso8601String(),
        ];
    }

    /**
     * Resuelve la colación acento/insensible configurada para búsquedas.
     *
     * @return string
     */
    private function resolveAccentInsensitiveCollation(): string
    {
        $defaultConnection = config('database.default');
        $connectionConfig = config("database.connections.{$defaultConnection}", []);

        return $connectionConfig['search_collation']
            ?? $connectionConfig['collation']
            ?? 'utf8mb4_unicode_ci';
    }
}

