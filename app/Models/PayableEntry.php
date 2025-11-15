<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class PayableEntry extends Model
{
    use BelongsToCompany;
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'payable_category_id',
        'movement_date',
        'concept',
        'description',
        'amount_cents',
        'currency_code',
        'reference',
        'is_paid',
        'paid_at',
    ];

    protected $casts = [
        'movement_date' => 'date',
        'amount_cents' => 'int',
        'is_paid' => 'bool',
        'paid_at' => 'datetime',
    ];

    protected $appends = [
        'amount_formatted',
        'movement_date_formatted',
        'status_label',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (PayableEntry $entry): void {
            if (! $entry->getAttribute('id')) {
                $entry->setAttribute('id', (string) Str::uuid());
            }

            if (! $entry->getAttribute('currency_code')) {
                $entry->setAttribute('currency_code', 'USD');
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PayableCategory::class, 'payable_category_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
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

        return $date ? Carbon::parse($date)->translatedFormat('d F Y') : null;
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->is_paid
            ? gettext('Pagada')
            : gettext('Pendiente');
    }
}

