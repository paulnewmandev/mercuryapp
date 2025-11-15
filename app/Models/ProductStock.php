<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductStock extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $table = 'product_stock';

    protected $primaryKey = null;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'quantity',
        'minimum_stock',
    ];

    public $timestamps = false;

    protected $casts = [
        'quantity' => 'int',
        'minimum_stock' => 'int',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}

