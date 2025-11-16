<?php

namespace App\Http\Controllers\Configuration;

use App\Contracts\SeoMetaManagerContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentSequences\StoreDocumentSequenceRequest;
use App\Http\Requests\DocumentSequences\UpdateDocumentSequenceRequest;
use App\Models\Company;
use App\Models\DocumentSequence;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DocumentSequenceController extends Controller
{
    /**
     * @var array<string, string>
     */
    private array $documentTypes;

    public function __construct(private readonly SeoMetaManagerContract $seoMetaManager)
    {
        $this->documentTypes = config('document_sequences.types', []);
    }

    public function index(): View
    {
        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Secuenciales',
            'description' => 'Configura los documentos secuenciales de la empresa.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Configuración'), 'url' => route('configuration.company')],
            ['label' => gettext('Secuenciales')],
        ];

        $documentTypes = collect($this->documentTypes)
            ->map(fn (string $label, string $value) => [
                'value' => $value,
                'label' => $label,
            ])
            ->values();

        return view('Configuration.DocumentSequences', compact('meta', 'breadcrumbItems', 'documentTypes'));
    }

    public function list(Request $request): JsonResponse
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        $sortBy = $request->string('sort_by', 'document_type')->toString();
        $sortDirection = Str::lower($request->string('sort_direction', 'asc')->toString()) === 'desc' ? 'desc' : 'asc';
        $perPage = (int) $request->integer('per_page', 10);
        $search = Str::of($request->string('search')->toString())
            ->replaceMatches('/\s+/', ' ')
            ->trim();

        // Si el usuario no tiene compañía (super admin), obtener la primera compañía disponible
        if (! $companyId) {
            $company = Company::query()->first();
            $companyId = $company?->id;
        }

        $allowedSorts = ['document_type', 'current_sequence', 'status', 'created_at'];
        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'document_type';
        }

        $allowedPerPage = [5, 10, 20, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $accentInsensitiveCollation = $this->resolveAccentInsensitiveCollation();
        $searchableColumns = [
            'document_sequences.name',
            'document_sequences.document_type',
            'document_sequences.establishment_code',
            'document_sequences.emission_point_code',
        ];
        $grammar = DB::query()->getGrammar();

        $tokens = $search->isEmpty()
            ? collect()
            : collect(explode(' ', Str::ascii(Str::lower($search))))
                ->filter()
                ->unique()
                ->values();

        $query = DocumentSequence::query()
            ->when($companyId, fn ($builder) => $builder->where('document_sequences.company_id', $companyId))
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
                                        $statusQuery->orWhere('document_sequences.status', $statusCode);
                                    }
                                });
                            }
                        });
                    });
                });
            });

        $query->orderBy("document_sequences.{$sortBy}", $sortDirection);

        $paginator = $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (DocumentSequence $sequence) => $this->transformSequence($sequence));

        return response()->json([
            'status' => 'success',
            'message' => gettext('Secuenciales obtenidos correctamente.'),
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

    public function show(DocumentSequence $documentSequence): JsonResponse
    {
        $this->ensureSequenceBelongsToCompany($documentSequence);

        return response()->json([
            'status' => 'success',
            'message' => gettext('Secuencial obtenido correctamente.'),
            'data' => [
                'item' => $this->transformSequence($documentSequence->loadMissing('company')),
            ],
        ]);
    }

    public function store(StoreDocumentSequenceRequest $request): JsonResponse
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        // Si el usuario no tiene compañía (super admin), obtener la primera compañía disponible
        if (! $companyId) {
            $company = Company::query()->first();
            $companyId = $company?->id;
            
            if (! $companyId) {
                return response()->json([
                    'status' => 'error',
                    'message' => gettext('No hay empresas registradas en el sistema.'),
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        $validated = $request->validated();

        $sequence = new DocumentSequence();
        $sequence->fill($validated);
        $sequence->company_id = $companyId;
        $sequence->name = Arr::get($this->documentTypes, $validated['document_type'], $validated['document_type']);
        $sequence->status = $validated['status'] ?? 'A';
        $sequence->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El secuencial se creó correctamente.'),
            'data' => [
                'item' => $this->transformSequence($sequence->refresh()),
            ],
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateDocumentSequenceRequest $request, DocumentSequence $documentSequence): JsonResponse
    {
        $this->ensureSequenceBelongsToCompany($documentSequence);

        $validated = $request->validated();

        $documentSequence->fill($validated);
        $documentSequence->name = Arr::get($this->documentTypes, $validated['document_type'], $validated['document_type']);
        $documentSequence->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El secuencial se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformSequence($documentSequence->refresh()),
            ],
        ]);
    }

    public function destroy(DocumentSequence $documentSequence): JsonResponse
    {
        $this->ensureSequenceBelongsToCompany($documentSequence);

        $deletedId = $documentSequence->id;
        $documentSequence->delete();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El secuencial se eliminó correctamente.'),
            'data' => [
                'deleted_id' => $deletedId,
            ],
        ]);
    }

    public function toggleStatus(Request $request, DocumentSequence $documentSequence): JsonResponse
    {
        $this->ensureSequenceBelongsToCompany($documentSequence);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['A', 'I', 'T'])],
        ]);

        $documentSequence->status = $validated['status'];
        $documentSequence->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El estado se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformSequence($documentSequence->refresh()),
            ],
        ]);
    }

    private function transformSequence(DocumentSequence $sequence): array
    {
        $sequence->loadMissing('company');

        $formattedSecuencial = sprintf(
            '%s-%s-%s',
            strtoupper($sequence->establishment_code),
            strtoupper($sequence->emission_point_code),
            str_pad((string) $sequence->current_sequence, 3, '0', STR_PAD_LEFT)
        );

        $statusLabel = $sequence->status_label;

        return [
            'id' => $sequence->id,
            'name' => $sequence->name,
            'document_type' => $sequence->document_type,
            'document_type_label' => Arr::get($this->documentTypes, $sequence->document_type, $sequence->name),
            'company_id' => $sequence->company_id,
            'company_name' => $sequence->company?->name,
            'establishment_code' => $sequence->establishment_code,
            'emission_point_code' => $sequence->emission_point_code,
            'current_sequence' => $sequence->current_sequence,
            'formatted_sequence' => $formattedSecuencial,
            'status' => $sequence->status,
            'status_label' => $statusLabel,
            'is_active' => $sequence->status === 'A',
            'display_name' => sprintf('%s · %s', Arr::get($this->documentTypes, $sequence->document_type, $sequence->name), $sequence->company?->name ?? '-'),
            'created_at' => $sequence->created_at?->toIso8601String(),
            'updated_at' => $sequence->updated_at?->toIso8601String(),
        ];
    }

    private function ensureSequenceBelongsToCompany(DocumentSequence $sequence): void
    {
        $user = Auth::user();
        if (! $user) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $userCompanyId = $user->company_id;
        
        // Si el usuario no tiene compañía (super admin), obtener la primera compañía disponible
        if (! $userCompanyId) {
            $company = Company::query()->first();
            $userCompanyId = $company?->id;
        }

        if ($userCompanyId && $sequence->company_id !== $userCompanyId) {
            abort(Response::HTTP_NOT_FOUND);
        }
    }

    private function resolveAccentInsensitiveCollation(): string
    {
        $connection = config('database.default');

        return match (config("database.connections.{$connection}.driver")) {
            'mysql', 'mariadb' => 'utf8mb4_unicode_ci',
            default => 'utf8mb4_general_ci',
        };
    }
}

