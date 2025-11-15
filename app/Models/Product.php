<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;
    use BelongsToCompany;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'product_line_id',
        'category_id',
        'subcategory_id',
        'warehouse_id',
        'sku',
        'barcode',
        'name',
        'description',
        'image_url',
        'featured_image_path',
        'gallery_images',
        'show_in_pos',
        'show_in_b2b',
        'show_in_b2c',
        'price_list_pos_id',
        'price_list_b2c_id',
        'price_list_b2b_id',
        'status',
    ];

    protected $casts = [
        'show_in_pos' => 'boolean',
        'show_in_b2b' => 'boolean',
        'show_in_b2c' => 'boolean',
        'gallery_images' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (Product $product): void {
            if (! $product->getAttribute('id')) {
                $product->setAttribute('id', (string) Str::uuid());
            }
        });
    }

    public function line(): BelongsTo
    {
        return $this->belongsTo(ProductLine::class, 'product_line_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'subcategory_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function priceListPos(): BelongsTo
    {
        return $this->belongsTo(PriceList::class, 'price_list_pos_id');
    }

    public function priceListB2c(): BelongsTo
    {
        return $this->belongsTo(PriceList::class, 'price_list_b2c_id');
    }

    public function priceListB2b(): BelongsTo
    {
        return $this->belongsTo(PriceList::class, 'price_list_b2b_id');
    }
}

