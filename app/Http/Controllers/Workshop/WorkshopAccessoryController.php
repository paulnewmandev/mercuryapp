<?php

namespace App\Http\Controllers\Workshop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Workshop\Accessories\StoreWorkshopAccessoryRequest;
use App\Models\WorkshopAccessory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkshopAccessoryController extends Controller
{
    public function list(Request $request): JsonResponse
    {
        $search = Str::of($request->string('search')->toString())
            ->replaceMatches('/\s+/', ' ')
            ->trim();

        $perPage = (int) $request->integer('per_page', 10);
        $allowedPerPage = [5, 10, 20, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $accentInsensitiveCollation = $this->resolveAccentInsensitiveCollation();
        $grammar = DB::query()->getGrammar();

        $tokens = $search->isEmpty()
            ? collect()
            : collect(explode(' ', Str::ascii(Str::lower($search))))
                ->filter()
                ->unique()
                ->values();

        $query = WorkshopAccessory::query()
            ->when($tokens->isNotEmpty(), function ($builder) use ($tokens, $accentInsensitiveCollation, $grammar): void {
                $builder->where(function ($outer) use ($tokens, $accentInsensitiveCollation, $grammar): void {
                    $tokens->each(function (string $token) use ($outer, $accentInsensitiveCollation, $grammar): void {
                        $outer->where(function ($inner) use ($token, $accentInsensitiveCollation, $grammar): void {
                            $wrapped = $grammar->wrap('name');
                            $inner->orWhereRaw(
                                "LOWER(CONVERT({$wrapped} USING utf8mb4) COLLATE {$accentInsensitiveCollation}) LIKE ?",
                                ["%{$token}%"]
                            );
                        });
                    });
                });
            })
            ->orderBy('name');

        $paginator = $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (WorkshopAccessory $accessory) => $this->transformAccessory($accessory));

        return response()->json([
            'status' => 'success',
            'message' => gettext('Accesorios obtenidos correctamente.'),
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

    public function options(Request $request): JsonResponse
    {
        $search = Str::of($request->string('search')->toString())
            ->replaceMatches('/\s+/', ' ')
            ->trim();

        $accentInsensitiveCollation = $this->resolveAccentInsensitiveCollation();
        $grammar = DB::query()->getGrammar();

        $query = WorkshopAccessory::query()
            ->where('status', 'A')
            ->orderBy('name');

        if ($search->isNotEmpty()) {
            $token = Str::ascii(Str::lower($search));
            $wrapped = $grammar->wrap('name');
            $query->whereRaw(
                "LOWER(CONVERT({$wrapped} USING utf8mb4) COLLATE {$accentInsensitiveCollation}) LIKE ?",
                ["%{$token}%"]
            );
        }

        $items = $query->limit(50)->get()->map(fn (WorkshopAccessory $accessory) => [
            'id' => $accessory->id,
            'name' => $accessory->name,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => gettext('Accesorios obtenidos correctamente.'),
            'data' => [
                'items' => $items,
            ],
        ]);
    }

    public function store(StoreWorkshopAccessoryRequest $request): JsonResponse
    {
        $user = Auth::user();

        $accessory = new WorkshopAccessory($request->validated());
        $accessory->company()->associate($user?->company_id);
        $accessory->status = 'A';
        $accessory->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El accesorio se creó correctamente.'),
            'data' => [
                'item' => $this->transformAccessory($accessory->refresh()),
            ],
        ]);
    }

    private function transformAccessory(WorkshopAccessory $accessory): array
    {
        return [
            'id' => $accessory->id,
            'name' => $accessory->name,
            'status' => $accessory->status,
            'status_label' => match ($accessory->status) {
                'A' => gettext('Activo'),
                'I' => gettext('Inactivo'),
                'T' => gettext('En papelera'),
                default => $accessory->status ?? '',
            },
            'created_at' => optional($accessory->created_at)->toIso8601String(),
            'updated_at' => optional($accessory->updated_at)->toIso8601String(),
        ];
    }

    private function resolveAccentInsensitiveCollation(): string
    {
        $defaultConnection = config('database.default');
        $connectionConfig = config("database.connections.{$defaultConnection}", []);

        return $connectionConfig['search_collation']
            ?? $connectionConfig['collation']
            ?? 'utf8mb4_0900_ai_ci';
    }
}
