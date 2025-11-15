<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Modelo Eloquent que representa las sucursales asociadas a una compañía.
 *
 * @property string $id
 * @property string $company_id
 * @property string $code
 * @property string $name
 * @property string|null $address
 * @property string|null $website
 * @property string|null $email
 * @property string|null $phone_number
 * @property float|null $latitude
 * @property float|null $longitude
 * @property string $status
 */
class Branch extends Model
{
    use HasFactory;
    use BelongsToCompany;

    /**
     * Indica que la llave primaria es un UUID.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Tipo de dato de la llave primaria.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Atributos asignables en masa.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'address',
        'website',
        'email',
        'phone_number',
        'latitude',
        'longitude',
        'status',
    ];

    /**
     * Atributos calculados que se anexan automáticamente.
     *
     * @var list<string>
     */
    protected $appends = [
        'status_label',
    ];

    /**
     * Hook de inicialización del modelo.
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (Branch $branch): void {
            if (! $branch->getAttribute('id')) {
                $branch->setAttribute('id', (string) Str::uuid());
            }
        });
    }

    /**
     * Obtiene la compañía propietaria de la sucursal.
     *
     * @return BelongsTo<Company, Branch>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Devuelve la etiqueta legible del estado.
     *
     * @return string
     */
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

