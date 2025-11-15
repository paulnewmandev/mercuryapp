<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use App\Models\Company;

class Customer extends Model
{
    use HasFactory;
    use BelongsToCompany;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'category_id',
        'customer_type',
        'first_name',
        'last_name',
        'business_name',
        'sex',
        'birth_date',
        'document_type',
        'document_number',
        'email',
        'phone_number',
        'address',
        'portal_password',
        'portal_password_changed_at',
        'b2b_access',
        'b2c_access',
        'status',
    ];

    protected $hidden = [
        'portal_password',
    ];

    protected $appends = [
        'status_label',
        'display_name',
        'category_name',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'b2b_access' => 'boolean',
        'b2c_access' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (Customer $customer): void {
            if (! $customer->getAttribute('id')) {
                $customer->setAttribute('id', (string) Str::uuid());
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CustomerCategory::class, 'category_id');
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

    public function getDisplayNameAttribute(): string
    {
        if ($this->customer_type === 'business' && $this->business_name) {
            return $this->business_name;
        }

        $name = trim(collect([$this->first_name, $this->last_name])->filter()->implode(' '));

        return $name !== '' ? $name : ($this->business_name ?? gettext('Sin nombre'));
    }

    public function getCategoryNameAttribute(): ?string
    {
        return optional($this->category)->name;
    }
}
