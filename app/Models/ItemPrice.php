<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ItemPrice extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'item_id',
        'item_type',
        'price_list_id',
        'value',
        'status',
    ];

    protected $casts = [
        'value' => 'float',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (ItemPrice $price): void {
            if (! $price->getAttribute('id')) {
                $price->setAttribute('id', (string) Str::uuid());
            }
        });
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }
}

