<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProductTransferItem extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'product_transfer_id',
        'product_id',
        'quantity',
        'notes',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (ProductTransferItem $item): void {
            if (! $item->getAttribute('id')) {
                $item->setAttribute('id', (string) Str::uuid());
            }
        });
    }

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(ProductTransfer::class, 'product_transfer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

