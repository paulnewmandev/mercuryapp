<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkshopOrder extends Model
{
    use HasFactory;
    use BelongsToCompany;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'branch_id',
        'order_number',
        'category_id',
        'state_id',
        'customer_id',
        'equipment_id',
        'responsible_user_id',
        'priority',
        'note',
        'diagnosis',
        'warranty',
        'equipment_password',
        'promised_at',
        'budget_currency',
        'budget_amount',
        'advance_currency',
        'advance_amount',
        'total_cost',
        'total_paid',
        'balance',
        'status',
    ];

    protected $casts = [
        'diagnosis' => 'boolean',
        'warranty' => 'boolean',
        'budget_amount' => 'decimal:2',
        'advance_amount' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'balance' => 'decimal:2',
        'promised_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'status_label',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (WorkshopOrder $order): void {
            if (! $order->getAttribute('id')) {
                $order->setAttribute('id', (string) Str::uuid());
            }

            if (! $order->getAttribute('order_number') && $order->getAttribute('company_id')) {
                DB::transaction(function () use ($order): void {
                    $sequence = DB::table('document_sequences')
                        ->where('company_id', $order->company_id)
                        ->where('document_type', 'ORDEN_DE_TRABAJO')
                        ->where('status', 'A')
                        ->lockForUpdate()
                        ->first();

                    if ($sequence) {
                        $currentSequence = $sequence->current_sequence + 1;
                        DB::table('document_sequences')
                            ->where('id', $sequence->id)
                            ->update(['current_sequence' => $currentSequence]);
                        
                        // Formato: 001-XXX-YYY
                        // Primer grupo: siempre 001
                        // Segundo grupo: incrementa cada 999 órdenes (001, 002, 003...)
                        // Tercer grupo: va de 001 a 999, luego se resetea a 001
                        $secondGroup = (int) floor(($currentSequence - 1) / 999) + 1;
                        $thirdGroup = (($currentSequence - 1) % 999) + 1;
                        
                        $orderNumber = sprintf('001-%03d-%03d', $secondGroup, $thirdGroup);
                        $order->setAttribute('order_number', $orderNumber);
                    }
                });
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(WorkshopCategory::class, 'category_id');
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(WorkshopState::class, 'state_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(WorkshopEquipment::class, 'equipment_id');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function accessories(): BelongsToMany
    {
        return $this->belongsToMany(WorkshopAccessory::class, 'workshop_order_accessory', 'order_id', 'accessory_id');
    }

    public function advances(): HasMany
    {
        return $this->hasMany(WorkshopOrderAdvance::class, 'order_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(WorkshopOrderNote::class, 'order_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(WorkshopOrderItem::class, 'order_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(WorkshopOrderService::class, 'order_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'A' => gettext('Activo'),
            'I' => gettext('Inactivo'),
            'T' => gettext('En papelera'),
            default => $this->status ?? '',
        };
    }
}
