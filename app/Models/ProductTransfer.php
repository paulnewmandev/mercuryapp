<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ProductTransfer extends Model
{
    use BelongsToCompany;
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'origin_warehouse_id',
        'destination_warehouse_id',
        'movement_date',
        'reference',
        'notes',
        'status',
    ];

    protected $casts = [
        'movement_date' => 'date',
    ];

    protected $appends = [
        'status_label',
        'items_count',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (ProductTransfer $transfer): void {
            if (! $transfer->getAttribute('id')) {
                $transfer->setAttribute('id', (string) Str::uuid());
            }
        });
    }

    public function originWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'origin_warehouse_id');
    }

    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductTransferItem::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'A' => gettext('Completado'),
            'I' => gettext('Anulado'),
            'T' => gettext('En papelera'),
            default => $this->status,
        };
    }

    public function getItemsCountAttribute(): int
    {
        if ($this->relationLoaded('items')) {
            return $this->items->sum('quantity');
        }

        return (int) $this->items()->sum('quantity');
    }
}

