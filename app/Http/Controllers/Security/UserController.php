<?php

namespace App\Http\Controllers\Security;

use App\Contracts\SeoMetaManagerContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Models\User;
use App\Support\Tenant\CurrentCompany;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

class UserController extends Controller
{
    public function __construct(
        private readonly SeoMetaManagerContract $seoMetaManager,
        private readonly CurrentCompany $currentCompany
    ) {
    }

    public function index(): View
    {
        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Usuarios',
            'description' => 'Administra los usuarios internos de tu compañía en AVI.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Seguridad'), 'url' => route('security.users')],
            ['label' => gettext('Usuarios')],
        ];

        return view('Security.Users', [
            'meta' => $meta,
            'breadcrumbItems' => $breadcrumbItems,
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $sortBy = $request->string('sort_by', 'first_name')->toString();
        $sortDirection = Str::lower($request->string('sort_direction')->toString()) === 'desc' ? 'desc' : 'asc';
        $perPage = (int) $request->integer('per_page', 10);
        $allowedPerPage = [5, 10, 20, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $allowedSorts = ['first_name', 'last_name', 'email', 'status', 'created_at'];
        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'first_name';
        }

        $search = Str::of($request->string('search')->toString())
            ->replaceMatches('/\s+/', ' ')
            ->trim();

        $tokens = $this->tokenizeSearch($search);
        $accentInsensitiveCollation = $this->resolveAccentInsensitiveCollation();
        $searchableColumns = [
            'users.first_name',
            'users.last_name',
            'users.email',
            'users.document_number',
            'users.phone_number',
            'roles.display_name',
        ];

        $query = User::query()
            ->with('role')
            ->leftJoin('roles', 'roles.id', '=', 'users.role_id')
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
                                        $statusQuery->orWhere('users.status', $statusCode);
                                    }
                                });
                            }
                        });
                    });
                });
            })
            ->select('users.*')
            ->orderBy($sortBy, $sortDirection);

        $paginator = $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (User $user) => $this->transformUser($user->load('role')));

        return response()->json([
            'status' => 'success',
            'message' => gettext('Usuarios obtenidos correctamente.'),
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

    public function show(User $user): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => gettext('Usuario obtenido correctamente.'),
            'data' => [
                'item' => $this->transformUser($user->load('role')),
            ],
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $companyId = $this->currentCompany->id();

        $user = new User();
        $user->fill([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'role_id' => $data['role_id'],
            'password_hash' => Hash::make($data['password']),
            'document_number' => $data['document_number'] ?? null,
            'phone_number' => $data['phone_number'] ?? null,
            'status' => $data['status'],
        ]);
        $user->setAttribute('company_id', $companyId);
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El usuario se creó correctamente.'),
            'data' => [
                'item' => $this->transformUser($user->refresh()->load('role')),
            ],
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();

        $user->fill([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'role_id' => $data['role_id'],
            'document_number' => $data['document_number'] ?? null,
            'phone_number' => $data['phone_number'] ?? null,
            'status' => $data['status'],
        ]);

        if (! empty($data['password'])) {
            $user->password_hash = Hash::make($data['password']);
        }

        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El usuario se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformUser($user->refresh()->load('role')),
            ],
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        if ($user->id === Auth::id()) {
            return response()->json([
                'status' => 'error',
                'message' => gettext('No puedes eliminar tu propio usuario.'),
                'data' => [],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($user->company_id === null) {
            return response()->json([
                'status' => 'error',
                'message' => gettext('No se puede eliminar el usuario super administrador.'),
                'data' => [],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El usuario se eliminó correctamente.'),
            'data' => [],
        ]);
    }

    public function toggleStatus(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:A,I,T'],
        ]);

        if ($user->id === Auth::id()) {
            return response()->json([
                'status' => 'error',
                'message' => gettext('No puedes cambiar el estado de tu propio usuario.'),
                'data' => [],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($user->company_id === null) {
            return response()->json([
                'status' => 'error',
                'message' => gettext('No se puede cambiar el estado del usuario super administrador.'),
                'data' => [],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->status = $validated['status'];
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El estado del usuario se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformUser($user->refresh()->load('role')),
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

    private function transformUser(User $user): array
    {
        return [
            'id' => $user->id,
            'company_id' => $user->company_id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'display_name' => $user->display_name,
            'email' => $user->email,
            'document_number' => $user->document_number,
            'phone_number' => $user->phone_number,
            'role_id' => $user->role_id,
            'role_name' => optional($user->role)->display_name,
            'status' => $user->status,
            'status_label' => $user->status_label,
            'created_at' => optional($user->created_at)->toIso8601String(),
            'updated_at' => optional($user->updated_at)->toIso8601String(),
        ];
    }
}

