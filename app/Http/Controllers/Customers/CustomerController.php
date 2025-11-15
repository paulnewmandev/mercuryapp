<?php

namespace App\Http\Controllers\Customers;

use App\Contracts\SeoMetaManagerContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customers\StoreCustomerRequest;
use App\Http\Requests\Customers\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\CustomerCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function __construct(private readonly SeoMetaManagerContract $seoMetaManager)
    {
    }

    public function indexIndividuals(): View
    {
        return $this->renderCustomersView('individual');
    }

    public function indexBusinesses(): View
    {
        return $this->renderCustomersView('business');
    }

    public function list(Request $request): JsonResponse
    {
        $sortBy = $request->string('sort_by', 'created_at')->toString();
        $sortDirection = Str::lower($request->string('sort_direction', 'desc')->toString()) === 'desc' ? 'desc' : 'asc';
        $perPage = (int) $request->integer('per_page', 10);
        $search = Str::of($request->string('search')->toString())
            ->replaceMatches('/\s+/', ' ')
            ->trim();
        $customerTypeFilter = Str::lower($request->string('customer_type')->toString());
        $customerTypeFilter = in_array($customerTypeFilter, ['individual', 'business'], true) ? $customerTypeFilter : null;

        $allowedSorts = ['display_name', 'document_number', 'email', 'phone_number', 'birth_date', 'status', 'created_at', 'category_name'];
        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
            $sortDirection = 'desc';
        }

        $allowedPerPage = [5, 10, 20, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $accentInsensitiveCollation = $this->resolveAccentInsensitiveCollation();
        $searchableColumns = [
            'customers.first_name',
            'customers.last_name',
            'customers.business_name',
            'customers.document_number',
            'customers.email',
            'customers.phone_number',
            'customers.sex',
            'customer_categories.name',
        ];
        $grammar = DB::query()->getGrammar();

        $tokens = $search->isEmpty()
            ? collect()
            : collect(explode(' ', Str::ascii(Str::lower($search))))
                ->filter()
                ->unique()
                ->values();

        $query = Customer::query()
            ->with('category')
            ->leftJoin('customer_categories', 'customer_categories.id', '=', 'customers.category_id')
            ->select('customers.*')
            ->when($customerTypeFilter, fn ($builder) => $builder->where('customers.customer_type', $customerTypeFilter))
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
            });

        if ($sortBy === 'display_name') {
            $query->orderByRaw("COALESCE(NULLIF(customers.business_name, ''), CONCAT(COALESCE(customers.first_name, ''), ' ', COALESCE(customers.last_name, ''))) {$sortDirection}");
        } elseif ($sortBy === 'category_name') {
            $query->orderBy('customer_categories.name', $sortDirection);
        } elseif ($sortBy === 'birth_date') {
            $query->orderBy('customers.birth_date', $sortDirection);
        } else {
            $query->orderBy("customers.{$sortBy}", $sortDirection);
        }

        $paginator = $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Customer $customer) => $this->transformCustomer($customer));

        return response()->json([
            'status' => 'success',
            'message' => gettext('Clientes obtenidos correctamente.'),
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

    public function show(Customer $customer): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => gettext('Cliente obtenido correctamente.'),
            'data' => [
                'item' => $this->transformCustomer($customer),
            ],
        ]);
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validated();
        $portalPassword = $validated['portal_password'] ?? null;
        $validated['b2b_access'] = isset($validated['b2b_access']) ? (bool) $validated['b2b_access'] : false;
        $validated['b2c_access'] = isset($validated['b2c_access']) ? (bool) $validated['b2c_access'] : false;

        unset($validated['portal_password']);

        if ($portalPassword) {
            $validated['portal_password'] = Hash::make($portalPassword);
            $validated['portal_password_changed_at'] = now();
        } else {
            $validated['portal_password'] = null;
            $validated['portal_password_changed_at'] = null;
        }

        $customer = new Customer($validated);
        $customer->company()->associate($user->company_id);
        $customer->status = $customer->status ?? 'A';
        $customer->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El cliente se creó correctamente.'),
            'data' => [
                'item' => $this->transformCustomer($customer->refresh()),
            ],
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        $validated = $request->validated();
        $portalPassword = $validated['portal_password'] ?? null;

        if (array_key_exists('b2b_access', $validated)) {
            $validated['b2b_access'] = (bool) $validated['b2b_access'];
        }

        if (array_key_exists('b2c_access', $validated)) {
            $validated['b2c_access'] = (bool) $validated['b2c_access'];
        }

        unset($validated['portal_password']);

        if ($portalPassword) {
            $validated['portal_password'] = Hash::make($portalPassword);
            $validated['portal_password_changed_at'] = now();
        }

        $customer->fill($validated);
        $customer->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El cliente se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformCustomer($customer->refresh()),
            ],
        ]);
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $deletedId = $customer->id;
        $customer->delete();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El cliente se eliminó correctamente.'),
            'data' => [
                'deleted_id' => $deletedId,
            ],
        ]);
    }

    public function toggleStatus(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['A', 'I', 'T'])],
        ]);

        $customer->status = $validated['status'];
        $customer->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El estado se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformCustomer($customer->refresh()),
            ],
        ]);
    }

    public function validateDocument(Request $request): JsonResponse
    {
        $data = $request->validate([
            'document_type' => ['required', Rule::in(['RUC', 'CEDULA', 'PASAPORTE'])],
            'document_number' => ['required', 'string', 'max:20'],
            'customer_id' => ['nullable', 'uuid'],
        ]);

        $documentNumber = Str::upper(trim($data['document_number']));
        $customerId = $data['customer_id'] ?? null;
        $documentType = Str::upper(trim($data['document_type']));
        $companyId = Auth::user()?->company_id;

        $exists = Customer::query()
            ->where('company_id', $companyId)
            ->where('document_type', $documentType)
            ->when($customerId, fn ($builder) => $builder->where('id', '!=', $customerId))
            ->where('document_number', $documentNumber)
            ->exists();

        $available = ! $exists;

        return response()->json([
            'status' => 'success',
            'message' => $available
                ? gettext('El número de documento está disponible.')
                : gettext('El número de documento ya está registrado.'),
            'data' => [
                'available' => $available,
                'document_type' => $documentType,
                'document_number' => $documentNumber,
            ],
        ]);
    }

    public function validateEmail(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['nullable', 'email', 'max:255'],
            'customer_id' => ['nullable', 'uuid'],
        ]);

        $email = $data['email'] ? Str::lower(trim($data['email'])) : null;
        $customerId = $data['customer_id'] ?? null;
        $companyId = Auth::user()?->company_id;

        if ($email === null || $email === '') {
            return response()->json([
                'status' => 'success',
                'message' => gettext('No se requiere validación para un correo vacío.'),
                'data' => [
                    'available' => true,
                    'email' => $email,
                ],
            ]);
        }

        $exists = Customer::query()
            ->where('company_id', $companyId)
            ->when($customerId, fn ($builder) => $builder->where('id', '!=', $customerId))
            ->where('email', $email)
            ->exists();

        $available = ! $exists;

        return response()->json([
            'status' => 'success',
            'message' => $available
                ? gettext('El correo electrónico está disponible.')
                : gettext('El correo electrónico ya está registrado.'),
            'data' => [
                'available' => $available,
                'email' => $email,
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

    private function transformCustomer(Customer $customer): array
    {
        $sex = Str::upper((string) $customer->sex);
        $sexLabel = match ($sex) {
            'MASCULINO' => gettext('Masculino'),
            'FEMENINO' => gettext('Femenino'),
            'OTRO' => gettext('Otro'),
            default => gettext('No especifica'),
        };

        $birthDate = $customer->birth_date;

        $createdAt = $customer->created_at instanceof Carbon
            ? $customer->created_at->timezone(config('app.timezone', 'UTC'))
            : null;

        return [
            'id' => $customer->id,
            'category_id' => $customer->category_id,
            'category_name' => $customer->category_name,
            'customer_type' => $customer->customer_type,
            'first_name' => $customer->first_name,
            'last_name' => $customer->last_name,
            'business_name' => $customer->business_name,
            'display_name' => $customer->display_name,
            'name' => $customer->display_name,
            'sex' => $sex,
            'sex_label' => $sexLabel,
            'birth_date' => optional($birthDate)->toDateString(),
            'birth_date_label' => $birthDate ? $birthDate->format('d/m/Y') : gettext('No especifica'),
            'document_type' => $customer->document_type,
            'document_number' => $customer->document_number,
            'email' => $customer->email,
            'phone_number' => $customer->phone_number,
            'address' => $customer->address,
            'b2b_access' => (bool) $customer->b2b_access,
            'b2b_access_label' => $customer->b2b_access ? gettext('Sí') : gettext('No'),
            'b2c_access' => (bool) $customer->b2c_access,
            'b2c_access_label' => $customer->b2c_access ? gettext('Sí') : gettext('No'),
            'has_portal_password' => (bool) $customer->portal_password,
            'portal_access_label' => $customer->portal_password ? gettext('Configurada') : gettext('No configurada'),
            'status' => $customer->status,
            'status_label' => $customer->status_label,
            'created_at' => optional($customer->created_at)->toIso8601String(),
            'created_at_label' => $createdAt ? $createdAt->format('d/m/Y H:i') : gettext('No especifica'),
            'updated_at' => optional($customer->updated_at)->toIso8601String(),
        ];
    }

    private function renderCustomersView(string $scope): View
    {
        $user = Auth::user();
        $isBusiness = $scope === 'business';

        $meta = $this->seoMetaManager->compose([
            'title' => $isBusiness ? 'MercuryApp · Empresas' : 'MercuryApp · Clientes naturales',
            'description' => $isBusiness
                ? 'Gestiona la información de tus clientes empresariales.'
                : 'Gestiona la información de tus clientes personas naturales.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Clientes'), 'url' => $isBusiness ? route('clientes.empresas') : route('clientes.naturales')],
            ['label' => $isBusiness ? gettext('Empresas') : gettext('Clientes naturales')],
        ];

        $categories = CustomerCategory::query()
            ->where('company_id', $user->company_id)
            ->where('status', 'A')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (CustomerCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
            ])
            ->values();

        $customerPageConfig = [
            'customerType' => $scope,
            'title' => $isBusiness ? gettext('Empresas') : gettext('Clientes naturales'),
            'tableId' => $isBusiness ? 'business-customers-table' : 'individual-customers-table',
            'apiUrl' => route('clientes.data', ['customer_type' => $scope]),
            'categoryOptions' => $categories,
        ];

        return view('CRM.Customers', compact('meta', 'breadcrumbItems', 'customerPageConfig'));
    }
}
