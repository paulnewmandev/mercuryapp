<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\WorkshopState;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class WorkshopCategory extends Model
{
    use HasFactory;
    use BelongsToCompany;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'status_label',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (WorkshopCategory $category): void {
            if (! $category->getAttribute('id')) {
                $category->setAttribute('id', (string) Str::uuid());
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function states(): HasMany
    {
        return $this->hasMany(WorkshopState::class, 'category_id');
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


