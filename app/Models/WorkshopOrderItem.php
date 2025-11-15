<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WorkshopOrderItem extends Model
{
    use HasFactory;
    use BelongsToCompany;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'order_id',
        'product_id',
        'quantity',
        'unit_price',
        'notes',
        'status',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    protected $appends = [
        'status_label',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (WorkshopOrderItem $item): void {
            if (! $item->getAttribute('id')) {
                $item->setAttribute('id', (string) Str::uuid());
            }
        });

        // subtotal is a generated column, so we don't set it manually
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(WorkshopOrder::class, 'order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'A' => gettext('Activo'),
            'I' => gettext('Inactivo'),
            'T' => gettext('En papelera'),
            default => $this->status,
        };
    }
}
