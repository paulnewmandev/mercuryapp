<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PaymentCard extends Model
{
    use HasFactory;
    use BelongsToCompany;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'description',
        'status',
    ];

    protected $appends = [
        'status_label',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(static function (PaymentCard $card): void {
            if (! $card->getAttribute('id')) {
                $card->setAttribute('id', (string) Str::uuid());
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
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
