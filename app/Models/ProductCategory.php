<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ProductCategory extends Model
{
    use HasFactory;
    use BelongsToCompany;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'product_line_id',
        'parent_id',
        'status',
    ];

    protected $appends = [
        'status_label',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (ProductCategory $category): void {
            if (! $category->getAttribute('id')) {
                $category->setAttribute('id', (string) Str::uuid());
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ProductCategory::class, 'parent_id');
    }

    public function line(): BelongsTo
    {
        return $this->belongsTo(ProductLine::class, 'product_line_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'A' => __('Activo'),
            'I' => __('Inactivo'),
            'T' => __('En papelera'),
            default => (string) $this->status,
        };
    }
}
