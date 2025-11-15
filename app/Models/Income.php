<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class Income extends Model
{
    use BelongsToCompany;
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'income_type_id',
        'movement_date',
        'concept',
        'description',
        'amount_cents',
        'currency_code',
        'reference',
        'status',
    ];

    protected $casts = [
        'movement_date' => 'date',
        'amount_cents' => 'int',
    ];

    protected $appends = [
        'status_label',
        'amount_formatted',
        'movement_date_formatted',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (Income $income): void {
            if (! $income->getAttribute('id')) {
                $income->setAttribute('id', (string) Str::uuid());
            }

            if (! $income->getAttribute('currency_code')) {
                $income->setAttribute('currency_code', 'USD');
            }
        });
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(IncomeType::class, 'income_type_id');
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

    public function getAmountFormattedAttribute(): string
    {
        $amount = (int) $this->amount_cents / 100;
        return Number::currency($amount, $this->currency_code ?? 'USD', app()->getLocale());
    }

    public function getMovementDateFormattedAttribute(): ?string
    {
        $date = $this->movement_date;

        if ($date instanceof Carbon) {
            return $date->translatedFormat('d F Y');
        }

        if ($date) {
            return Carbon::parse($date)->translatedFormat('d F Y');
        }

        return null;
    }
}


