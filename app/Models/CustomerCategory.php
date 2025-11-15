<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CustomerCategory extends Model
{
    use HasFactory;
    use BelongsToCompany;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'description',
        'status',
    ];

    protected $appends = [
        'status_label',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (CustomerCategory $category): void {
            if (! $category->getAttribute('id')) {
                $category->setAttribute('id', (string) Str::uuid());
            }

            if (! $category->getAttribute('slug')) {
                $category->setAttribute('slug', Str::slug($category->getAttribute('name')));
            }
        });
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'category_id');
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

    /**
     * Resuelve el modelo para el route model binding.
     * Asegura que el scope global de compañía se aplique correctamente.
     *
     * @param mixed $value
     * @param string|null $field
     * @return Model|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $field = $field ?: $this->getRouteKeyName();
        
        return $this->where($field, $value)->first();
    }
}

