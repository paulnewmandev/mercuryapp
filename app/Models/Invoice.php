<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use HasFactory;
    use BelongsToCompany;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'branch_id',
        'customer_id',
        'salesperson_id',
        'invoice_number',
        'document_type',
        'source',
        'source_id',
        'subtotal',
        'tax_amount',
        'total_amount',
        'total_paid',
        'issue_date',
        'due_date',
        'workflow_status',
        'notes',
        'status',
        'access_key',
        'sri_status',
        'authorization_number',
        'authorized_at',
        'xml_signed',
        'xml_authorized',
        'sri_errors',
        'sri_environment',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'issue_date' => 'date',
        'due_date' => 'date',
        'authorized_at' => 'datetime',
        'sri_errors' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (Invoice $invoice): void {
            if (! $invoice->getAttribute('id')) {
                $invoice->setAttribute('id', (string) Str::uuid());
            }

            if (! $invoice->getAttribute('invoice_number') && $invoice->getAttribute('company_id')) {
                DB::transaction(function () use ($invoice): void {
                    $sequence = DB::table('document_sequences')
                        ->where('company_id', $invoice->company_id)
                        ->where('document_type', 'FACTURA')
                        ->where('status', 'A')
                        ->lockForUpdate()
                        ->first();

                    if ($sequence) {
                        $currentSequence = $sequence->current_sequence + 1;
                        DB::table('document_sequences')
                            ->where('id', $sequence->id)
                            ->update(['current_sequence' => $currentSequence]);
                        
                        // Formato: 001-001-000000001
                        // Primer grupo (001): establishment_code - número del establecimiento
                        // Segundo grupo (001): emission_point_code - número del facturero
                        // Tercer grupo (000000001): secuencial de 9 dígitos - número de la factura
                        $establishmentCode = $sequence->establishment_code ?? '001';
                        $emissionPointCode = $sequence->emission_point_code ?? '001';
                        $sequentialNumber = str_pad((string) $currentSequence, 9, '0', STR_PAD_LEFT);
                        
                        $invoiceNumber = sprintf('%s-%s-%s', $establishmentCode, $emissionPointCode, $sequentialNumber);
                        $invoice->setAttribute('invoice_number', $invoiceNumber);
                    }
                });
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }
}

