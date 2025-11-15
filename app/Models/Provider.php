<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Provider extends Model
{
    use HasFactory;
    use BelongsToCompany;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'provider_type',
        'identification_type',
        'identification_number',
        'first_name',
        'last_name',
        'business_name',
        'email',
        'phone_number',
        'status',
    ];

    protected $appends = [
        'display_name',
        'status_label',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (Provider $provider): void {
            if (! $provider->getAttribute('id')) {
                $provider->setAttribute('id', (string) Str::uuid());
            }
        });
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->provider_type === 'business' && $this->business_name) {
            return $this->business_name;
        }

        return trim(sprintf('%s %s', $this->first_name ?? '', $this->last_name ?? '')) ?: $this->business_name ?? '';
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

