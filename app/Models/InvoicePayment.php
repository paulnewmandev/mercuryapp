<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class InvoicePayment extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'invoice_id',
        'payment_method_id',
        'amount',
        'payment_date',
        'reference',
        'notes',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
    ];
    
    protected $appends = [
        'status_label',
    ];
    
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status ?? 'A') {
            'A' => gettext('Activo'),
            'I' => gettext('Inactivo'),
            'T' => gettext('En papelera'),
            default => $this->status ?? 'A',
        };
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (InvoicePayment $payment): void {
            if (! $payment->getAttribute('id')) {
                $payment->setAttribute('id', (string) Str::uuid());
            }
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    // Métodos de pago estáticos - ya no hay relación con PaymentMethod
    public function getPaymentMethodNameAttribute(): string
    {
        return match ($this->payment_method_id) {
            'EFECTIVO' => 'EFECTIVO',
            'TRANSFERENCIA' => 'TRANSFERENCIA',
            'TARJETA' => 'TARJETA',
            default => $this->payment_method_id ?? '',
        };
    }
}

