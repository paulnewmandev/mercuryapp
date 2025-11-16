<?php

namespace App\Http\Controllers\Security;

use App\Contracts\SeoMetaManagerContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\Permissions\StorePermissionRequest;
use App\Http\Requests\Permissions\UpdatePermissionRequest;
use App\Models\Permission;
use App\Support\Tenant\CurrentCompany;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

class PermissionController extends Controller
{
    public function __construct(
        private readonly SeoMetaManagerContract $seoMetaManager,
        private readonly CurrentCompany $currentCompany
    ) {
    }

    public function index(): View
    {
        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Permisos',
            'description' => 'Administra los permisos disponibles para los roles de tu empresa.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Seguridad'), 'url' => route('security.users')],
            ['label' => gettext('Permisos')],
        ];

        return view('Security.Permissions', [
            'meta' => $meta,
            'breadcrumbItems' => $breadcrumbItems,
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $sortBy = $request->string('sort_by', 'display_name')->toString();
        $sortDirection = Str::lower($request->string('sort_direction')->toString()) === 'desc' ? 'desc' : 'asc';
        $perPage = (int) $request->integer('per_page', 10);
        $allowedPerPage = [5, 10, 20, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $allowedSorts = ['display_name', 'name', 'status', 'created_at'];
        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'display_name';
        }

        $search = Str::of($request->string('search')->toString())
            ->replaceMatches('/\s+/', ' ')
            ->trim();

        $tokens = $this->tokenizeSearch($search);
        $accentInsensitiveCollation = $this->resolveAccentInsensitiveCollation();
        $searchableColumns = ['display_name', 'name', 'description'];

        $query = Permission::query()
            ->withCount('roles')
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
            ->through(fn (Permission $permission) => $this->transformPermission($permission));

        return response()->json([
            'status' => 'success',
            'message' => gettext('Permisos obtenidos correctamente.'),
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

    public function show(Permission $permission): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => gettext('Permiso obtenido correctamente.'),
            'data' => [
                'item' => $this->transformPermission($permission->loadCount('roles')),
            ],
        ]);
    }

    public function store(StorePermissionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $companyId = $this->currentCompany->id();

        $permission = new Permission();
        $permission->fill([
            'name' => $this->generateUniqueName($data['display_name'], $companyId),
            'display_name' => $data['display_name'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'],
        ]);
        $permission->setAttribute('company_id', $companyId);
        $permission->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El permiso se creó correctamente.'),
            'data' => [
                'item' => $this->transformPermission($permission->refresh()->loadCount('roles')),
            ],
        ], Response::HTTP_CREATED);
    }

    public function update(UpdatePermissionRequest $request, Permission $permission): JsonResponse
    {
        $data = $request->validated();
        $companyId = $permission->company_id;

        $permission->fill([
            'display_name' => $data['display_name'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'],
        ]);

        if (array_key_exists('display_name', $data)) {
            $permission->name = $this->generateUniqueName($data['display_name'], $companyId, $permission->id);
        }

        $permission->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El permiso se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformPermission($permission->refresh()->loadCount('roles')),
            ],
        ]);
    }

    public function destroy(Permission $permission): JsonResponse
    {
        if ($permission->roles()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => gettext('No se puede eliminar el permiso porque está asignado al menos a un rol.'),
                'data' => [],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $permission->delete();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El permiso se eliminó correctamente.'),
            'data' => [],
        ]);
    }

    public function toggleStatus(Request $request, Permission $permission): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:A,I,T'],
        ]);

        $permission->status = $validated['status'];
        $permission->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El estado del permiso se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformPermission($permission->refresh()->loadCount('roles')),
            ],
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        $search = Str::of($request->string('search')->toString())
            ->replaceMatches('/\s+/', ' ')
            ->trim();

        $tokens = $this->tokenizeSearch($search);
        $accentInsensitiveCollation = $this->resolveAccentInsensitiveCollation();

        $query = Permission::query()
            ->where('status', 'A')
            ->orderBy('display_name', 'asc')
            ->select(['id', 'display_name', 'name']);

        if ($tokens->isNotEmpty()) {
            $query->where(function ($outer) use ($tokens, $accentInsensitiveCollation): void {
                $grammar = DB::query()->getGrammar();

                $tokens->each(function (string $token) use ($outer, $accentInsensitiveCollation, $grammar): void {
                    $outer->where(function ($inner) use ($token, $accentInsensitiveCollation, $grammar): void {
                        foreach (['display_name', 'name'] as $column) {
                            $wrapped = $grammar->wrap($column);
                            $inner->orWhereRaw(
                                "LOWER(CONVERT({$wrapped} USING utf8mb4) COLLATE {$accentInsensitiveCollation}) LIKE ?",
                                ["%{$token}%"]
                            );
                        }
                    });
                });
            });
        }

        $items = $query->limit(100)->get()->map(fn (Permission $permission) => [
            'id' => $permission->id,
            'name' => $permission->name,
            'label' => $permission->display_name,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => gettext('Permisos obtenidos correctamente.'),
            'data' => [
                'items' => $items,
            ],
        ]);
    }

    private function tokenizeSearch(Stringable $search): Collection
    {
        return $search->isEmpty()
            ? collect()
            : collect(explode(' ', Str::ascii(Str::lower($search))))
                ->filter()
                ->unique()
                ->values();
    }

    private function resolveAccentInsensitiveCollation(): string
    {
        return config('database.connections.mysql.search_collation', 'utf8mb4_unicode_ci');
    }

    private function generateUniqueName(string $displayName, ?string $companyId, ?string $ignoreId = null): string
    {
        $base = Str::slug($displayName);
        if ($base === '') {
            $base = Str::lower(Str::ascii($displayName));
        }

        $candidate = $base;
        $suffix = 1;

        do {
            $exists = Permission::withoutGlobalScopes()
                ->when($companyId, fn ($query) => $query->where('company_id', $companyId), fn ($query) => $query->whereNull('company_id'))
                ->where('name', $candidate)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists();

            if (! $exists) {
                return $candidate;
            }

            $candidate = "{$base}-{$suffix}";
            $suffix++;
        } while (true);
    }

    private function transformPermission(Permission $permission): array
    {
        return [
            'id' => $permission->id,
            'company_id' => $permission->company_id,
            'name' => $permission->name,
            'display_name' => $permission->display_name,
            'description' => $permission->description,
            'status' => $permission->status,
            'status_label' => $permission->status_label,
            'roles_count' => $permission->roles_count ?? $permission->roles()->count(),
            'created_at' => optional($permission->created_at)->toIso8601String(),
            'updated_at' => optional($permission->updated_at)->toIso8601String(),
        ];
    }
}

